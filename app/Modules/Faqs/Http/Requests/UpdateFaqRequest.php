<?php

namespace App\Modules\Faqs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('faqs.update') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('faq')?->id;

        return [
            'title' => ['required', 'string', 'max:191'],

            'body' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
