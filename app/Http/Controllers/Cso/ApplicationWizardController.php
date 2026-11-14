<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModel;
use App\Models\Document;
use App\Models\OrganizationMember;
use App\Services\ApplicationSteps;
use App\Services\DocumentPrecheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The application pages after sign-up: members, the accreditation form (filled in online), the other
 * requirements, then review and submit (App\Services\ApplicationSteps). Each page saves to the draft
 * application, so the representative can leave and pick up on the first unfinished step.
 */
class ApplicationWizardController extends Controller
{
    /** Where "Apply for accreditation" leads: the open review, or the draft's first unfinished step. */
    public function resume(Request $request): RedirectResponse
    {
        $this->authorize('create', ApplicationModel::class);
        $organization = $request->user()->organization;

        if ($open = ApplicationSteps::inReview($organization)) {
            return redirect()->route('cso.applications.show', $open)
                ->with('status', 'Your application is with the Desk Office. You can apply again once it is decided.');
        }

        $draft = ApplicationSteps::draft($organization) ?? ApplicationSteps::start($organization, $request->user());

        // Keep a message meant for the step page (e.g. "Email confirmed") through this extra redirect.
        $request->session()->reflash();

        return redirect()->route('cso.application.step', ApplicationSteps::current($draft));
    }

    public function show(Request $request, string $step): View|RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }

        // Earlier steps can be revisited; later ones wait until those before them are done.
        $current = ApplicationSteps::current($draft);
        if (array_search($step, ApplicationSteps::PAGES, true) > array_search($current, ApplicationSteps::PAGES, true)) {
            return redirect()->route('cso.application.step', $current);
        }

        $organization = $draft->organization;

        return view("cso.application.{$step}", [
            'draft' => $draft->load('documents'),
            'organization' => $organization,
            'step' => $step,
            'done' => ApplicationSteps::done($draft),
            'uploads' => ApplicationSteps::uploads($draft),
            'members' => $step === 'review' ? $organization->members()->orderBy('id')->get() : null,
        ]);
    }

    public function saveMembers(Request $request): RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }

        // "Finish later" keeps a list that isn't complete yet; continuing needs all of it.
        $rules = OrganizationMember::rules();
        if ($request->input('action') === 'later') {
            $rules['members'] = ['required', 'array', 'max:300'];
        }
        $request->validate($rules, OrganizationMember::messages());
        if ($request->input('action') !== 'later' && $problem = OrganizationMember::representativeProblem($request->input('members'))) {
            return back()->withInput()->withErrors(['members' => $problem]);
        }

        $draft->organization->replaceMembers($request->input('members'));

        return $this->next($request, $draft, 'Members saved.');
    }

    public function saveForm(Request $request): RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['new', 'renewal'])],
            'organization_name' => ['required', 'string', 'max:255'],
            'representative_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:40'],
            'place_of_operation' => ['required', 'string', 'max:255'],
            'certify' => ['accepted'],
            'certified_name' => ['required', 'string', 'max:255'],
        ], [
            'certify.accepted' => 'Tick the box to certify that the form is true and correct.',
            'certified_name.required' => 'Type your full name to certify the form.',
        ]);

        // A renewal doesn't ask for the constitution; drop one uploaded while it was a new application.
        if ($data['type'] !== 'new') {
            $draft->documents()->where('document_type', 'constitution_bylaws')->get()->each(fn (Document $d) => $this->forget($d));
        }

        $draft->update([
            'type' => $data['type'],
            'form_data' => collect($data)->except(['type', 'certify'])->all() + ['certified_at' => now()->toIso8601String()],
        ]);

        return $this->next($request, $draft, 'Accreditation form saved.');
    }

    /** The form as it will be produced, from the answers saved so far. */
    public function preview(Request $request): Response|RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }

        return ApplicationSteps::formPdf($draft)->stream('accreditation-form-preview.pdf');
    }

    public function saveRequirements(Request $request): RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }

        $uploads = ApplicationSteps::uploads($draft);
        $request->validate([
            'documents' => ['array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'expires_at' => ['array'],
            'expires_at.*' => ['nullable', 'date', 'after:today'],
            'ocr_text' => ['nullable', 'array'],
            'ocr_text.*' => ['nullable', 'string', 'max:20000'],
        ], collect($uploads)->flatMap(fn (array $type, string $key) => [
            "documents.{$key}.mimes" => "{$type['label']} must be a PDF, JPG, or PNG file.",
            "documents.{$key}.max" => "{$type['label']} must be smaller than 5 MB.",
        ])->all());

        $organization = $draft->organization;
        foreach (array_keys($uploads) as $key) {
            $file = $request->file("documents.{$key}");
            $existing = $draft->documents()->where('document_type', $key)->first();

            if ($file) {
                $existing && $this->forget($existing);
                $check = DocumentPrecheck::evaluate($key, $request->input("ocr_text.{$key}"), $organization->name);
                $organization->documents()->create([
                    'application_id' => $draft->id,
                    'document_type' => $key,
                    'file_path' => $file->store("documents/{$organization->id}"),
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'expires_at' => $request->input("expires_at.{$key}") ?: null,
                    'ocr_status' => $check['status'],
                    'ocr_details' => $check['details'],
                ]);
            } elseif ($existing && $request->has("expires_at.{$key}")) {
                $existing->update(['expires_at' => $request->input("expires_at.{$key}") ?: null]);
            }
        }

        if ($request->input('action') === 'later') {
            return redirect()->route('cso.application.step', 'requirements')->with('status', 'Uploads saved. Come back any time to finish.');
        }

        $missing = collect($uploads)->reject(fn (array $type) => $type['optional'])
            ->keys()->diff($draft->documents()->pluck('document_type'));
        if ($missing->isNotEmpty()) {
            return redirect()->route('cso.application.step', 'requirements')->withErrors($missing->mapWithKeys(fn ($key) => [
                "documents.{$key}" => 'Upload your '.$uploads[$key]['label'].'.',
            ])->all());
        }

        return redirect()->route('cso.application.step', 'review')->with('status', 'Requirements saved.');
    }

    public function removeDocument(Request $request, Document $document): RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }
        abort_unless($document->application_id === $draft->id && $document->ocr_status !== 'generated', 404);

        $this->forget($document);

        return redirect()->route('cso.application.step', 'requirements')->with('status', 'File removed.');
    }

    public function submit(Request $request): RedirectResponse
    {
        [$draft, $redirect] = $this->draft($request);
        if ($redirect) {
            return $redirect;
        }

        if (($current = ApplicationSteps::current($draft)) !== 'review') {
            return redirect()->route('cso.application.step', $current)->withErrors(['step' => 'Finish this step before submitting.']);
        }

        ApplicationSteps::submit($draft, $request->user());

        return redirect()->route('cso.applications.show', $draft)
            ->with('status', 'Application submitted. The Desk Office will review it and you will be notified of any change.');
    }

    /** The organization's draft; without one, back to the start (which makes one or shows the review). */
    private function draft(Request $request): array
    {
        $this->authorize('create', ApplicationModel::class);
        $organization = $request->user()->organization;
        $draft = ApplicationSteps::draft($organization);

        return $draft ? [$draft, null] : [null, redirect()->route('cso.application')];
    }

    private function next(Request $request, ApplicationModel $draft, string $message): RedirectResponse
    {
        $order = ApplicationSteps::PAGES;
        $here = array_search($request->route()->parameter('step') ?? $request->input('step'), $order, true);
        $target = $request->input('action') === 'later' ? $order[$here] : ApplicationSteps::current($draft);

        return redirect()->route('cso.application.step', $target)
            ->with('status', $request->input('action') === 'later' ? "{$message} Come back any time to finish." : $message);
    }

    private function forget(Document $document): void
    {
        Storage::delete($document->file_path);
        $document->delete();
    }
}
