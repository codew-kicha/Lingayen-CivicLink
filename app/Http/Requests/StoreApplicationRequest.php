<?php

namespace App\Http\Requests;

use App\Models\ApplicationModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ApplicationModel::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_keys(config('document_types'));

        return [
            'type' => ['required', Rule::in(['new', 'renewal'])],

            'documents' => ['required', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'expires_at' => ['array'],
            'expires_at.*' => ['nullable', 'date', 'after:today'],
        ] + collect($types)
            ->mapWithKeys(fn (string $type) => ["documents.{$type}" => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']])
            ->all();
    }

    public function messages(): array
    {
        return collect(config('document_types'))
            ->mapWithKeys(fn (string $label, string $key) => [
                "documents.{$key}.required" => "Upload your {$label}.",
                "documents.{$key}.mimes" => "{$label} must be a PDF, JPG, or PNG file.",
                "documents.{$key}.max" => "{$label} must be smaller than 5 MB.",
            ])
            ->all();
    }
}
