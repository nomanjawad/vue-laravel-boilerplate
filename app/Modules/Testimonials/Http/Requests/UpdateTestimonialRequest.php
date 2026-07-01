<?php

namespace App\Modules\Testimonials\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('testimonials.update') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('testimonial')?->id;

        return [
            'title' => ['required', 'string', 'max:191'],

            'body' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
