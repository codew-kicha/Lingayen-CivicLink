<?php

namespace App\Http\Requests;

use App\Models\ApplicationModel;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // PESO files on a CSO's behalf through a route that names the organization (assisted encoding).
        return $this->route('organization') instanceof Organization
            ? $this->user()->can('manage', Organization::class)
            : $this->user()->can('create', ApplicationModel::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['new', 'renewal'])],

            'documents' => ['required', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'expires_at' => ['array'],
            'expires_at.*' => ['nullable', 'date', 'after:today'],
        ] + collect(config('document_types'))
            ->mapWithKeys(fn (array $type, string $key) => [
                "documents.{$key}" => [$type['optional'] ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ])
            ->all();
    }

    public function messages(): array
    {
        return collect(config('document_types'))
            ->mapWithKeys(fn (array $type, string $key) => [
                "documents.{$key}.required" => "Upload your {$type['label']}.",
                "documents.{$key}.mimes" => "{$type['label']} must be a PDF, JPG, or PNG file.",
                "documents.{$key}.max" => "{$type['label']} must be smaller than 5 MB.",
            ])
            ->all();
    }
}
