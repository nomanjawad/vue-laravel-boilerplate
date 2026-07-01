<?php

namespace App\Modules\Testimonials\Database\Factories;

use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

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
