<?php

namespace App\Modules\Events\Database\Factories;

use App\Modules\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->randomNumber(4),
            'body' => $this->faker->paragraphs(3, true),
            'is_active' => true,
            'published_at' => now(),
        ];
    }
}
