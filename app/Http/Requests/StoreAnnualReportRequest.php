<?php

namespace App\Http\Requests;

use App\Models\AnnualReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnnualReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('annual_report');

        return $report
            ? $this->user()->can('update', $report)
            : $this->user()->can('create', AnnualReport::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            // The file is only required when creating; editing may just retitle an existing report.
            'file' => [$this->route('annual_report') ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Annual reports must be uploaded as a PDF.',
            'file.max' => 'The report must be smaller than 10 MB.',
        ];
    }
}
