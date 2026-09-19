<?php

namespace App\Http\Controllers;

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
}
