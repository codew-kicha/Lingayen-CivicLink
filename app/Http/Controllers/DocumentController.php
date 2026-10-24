<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Accreditation documents hold member details and financial records, so they live on the
     * private disk and are only ever served through this authorized route (PRD §7, §8).
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document->organization);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
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
