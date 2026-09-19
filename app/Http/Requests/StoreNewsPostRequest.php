<?php

namespace App\Http\Requests;

use App\Models\NewsPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('news_post');

        return $post
            ? $this->user()->can('update', $post)
            : $this->user()->can('create', NewsPost::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'organizations' => ['array'],
            'organizations.*' => ['integer', Rule::exists('organizations', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['organizations' => 'featured organizations'];
    }
}
