<?php

namespace App\Modules\Faqs\Database\Factories;

use App\Modules\Faqs\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,

            'body' => $this->faker->paragraphs(3, true),
            'is_active' => true,
            'published_at' => now(),
        ];
    }
}
