<?php

namespace App\Modules\Testimonials\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Testimonials\Models\Testimonial;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Testimonials/Public/Testimonials/Index', [
            'testimonials' => Testimonial::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function show(Testimonial $testimonial): Response
    {
        abort_unless($testimonial->is_active, 404);

        return Inertia::render('Testimonials/Public/Testimonials/Show', [
            'testimonial' => $testimonial,
        ]);
    }
}
