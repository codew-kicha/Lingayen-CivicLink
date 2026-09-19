<?php

namespace App\Http\Controllers;

use App\Models\AnnualReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnualReportController extends Controller
{
    /**
     * Annual reports are public documents, but they still live on the private disk and are
     * served through this route rather than a guessable public path (PRD §8).
     */
    public function download(AnnualReport $annualReport): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($annualReport->file_path), 404);

        return Storage::disk('local')->download(
            $annualReport->file_path,
            Str::slug($annualReport->title).'.pdf',
        );
    }
}
