<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OfficeNotice;
use App\Models\Organization;
use App\Notifications\OfficeNoticeSent;
use App\Support\Mailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use function Illuminate\Support\defer;

/**
 * Notices from the Desk Office to organizations (pre-final defense panel): one organization, a sector,
 * a barangay, the accredited ones, or everyone. Each representative sees it under Notifications; with
 * "Also email" it goes to their inbox too, sent after the page has answered so a long list never
 * keeps the office waiting.
 */
class NoticeController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.notices.index', [
            'notices' => OfficeNotice::with('sender:id,name')->latest()->paginate(10),
        ]);
    }

    /** How many accounts an audience reaches, for the live count in the dialog. */
    public function count(Request $request): JsonResponse
    {
        $this->authorize('manage', Organization::class);
        $data = $request->validate($this->audienceRules());

        return response()->json(['recipients' => OfficeNotice::recipients($data['audience'], $data['audience_value'] ?? null)->count()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        $data = $request->validate($this->audienceRules() + [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'email' => ['nullable', 'boolean'],
        ]);

        $recipients = OfficeNotice::recipients($data['audience'], $data['audience_value'] ?? null)->get();
        if ($recipients->isEmpty()) {
            return back()->withInput()->withErrors(['audience' => 'No organization account matches this audience yet.']);
        }

        $notice = OfficeNotice::create([
            'sender_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'audience_value' => in_array($data['audience'], ['all', 'accredited'], true) ? null : $data['audience_value'],
            'recipients' => $recipients->count(),
            'emailed' => $request->boolean('email'),
        ]);

        Notification::send($recipients, new OfficeNoticeSent($notice));
        if ($notice->emailed) {
            defer(fn () => $recipients->filter->hasVerifiedEmail()
                ->each(fn ($user) => Mailer::send($user, new OfficeNoticeSent($notice, ['mail']))));
        }

        AuditLog::record('notice.sent', $notice, ['audience' => $notice->audienceLabel(), 'recipients' => $notice->recipients, 'emailed' => $notice->emailed]);

        return back()->with('status', "Notice sent to {$notice->recipients} ".str('organization')->plural($notice->recipients)
            .($notice->emailed ? '. Emails are going out now.' : '.'));
    }

    private function audienceRules(): array
    {
        return [
            'audience' => ['required', Rule::in(array_keys(OfficeNotice::AUDIENCES))],
            'audience_value' => [
                'nullable', 'required_if:audience,organization,sector,barangay', 'string', 'max:255',
                Rule::when($this->input('audience') === 'sector', [Rule::in(config('sectors'))]),
                Rule::when($this->input('audience') === 'barangay', [Rule::in(config('barangays'))]),
            ],
        ];
    }

    private function input(string $key): mixed
    {
        return request()->input($key);
    }
}
