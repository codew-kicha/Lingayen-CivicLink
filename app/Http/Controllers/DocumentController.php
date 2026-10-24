<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /** Types a browser may show in a tab. Uploads only pass validation as these (sniffed from content). */
    private const VIEWABLE = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * Accreditation documents hold member details and financial records, so they live on the
     * private disk and are only ever served through this authorized route (PRD §7, §8).
     * PDFs and images open in the browser (the reviewer reads them in a new tab) with the type
     * recorded at upload and nosniff; anything else is downloaded.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document->organization);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        if (! in_array($document->mime_type, self::VIEWABLE, true)) {
            return Storage::disk('local')->download($document->file_path, $document->original_filename);
        }

        return Storage::disk('local')->response($document->file_path, $document->original_filename, [
            'Content-Type' => $document->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    // An activity's sign-in sheet, shown inline so the review dialog can preview it and "Open file"
    // opens it in a browser tab. Same private disk and policy check as accreditation documents.
    public function evidence(Activity $activity): StreamedResponse
    {
        $this->authorize('view', $activity);

        abort_unless($activity->evidence_path && Storage::disk('local')->exists($activity->evidence_path), 404);

        return Storage::disk('local')->response($activity->evidence_path, $activity->evidence_filename, [
            'Content-Type' => $activity->evidence_mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
