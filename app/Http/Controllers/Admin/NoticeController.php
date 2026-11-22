<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeNotice;
use App\Models\Organization;
use App\Services\NoticeSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $data = $request->validate(NoticeSender::rules($request));

        return response()->json(['recipients' => OfficeNotice::recipients($data['audience'], $data['audience_value'] ?? null)->count()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        $data = $request->validate(NoticeSender::rules($request) + [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'email' => ['nullable', 'boolean'],
        ]);

        $notice = NoticeSender::send($request->user(), $data['title'], $data['body'], $data['audience'], $data['audience_value'] ?? null, $request->boolean('email'));
        if (! $notice) {
            return back()->withInput()->withErrors(['audience' => 'No organization account matches this audience yet.']);
        }

        return back()->with('status', "Notice sent to {$notice->recipients} ".str('organization')->plural($notice->recipients)
            .($notice->emailed ? '. Emails are going out now.' : '.'));
    }
}
