<?php

namespace App\Modules\Testimonials\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Testimonials\Http\Requests\StoreTestimonialRequest;
use App\Modules\Testimonials\Http\Requests\UpdateTestimonialRequest;
use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Testimonial::class);

        return Inertia::render('Testimonials/Admin/Testimonials/Index', [
            'testimonials' => Testimonial::query()
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Testimonial::class);

        return Inertia::render('Testimonials/Admin/Testimonials/Create');
    }

    public function store(StoreTestimonialRequest $request)
    {
        $testimonial = Testimonial::create($request->validated());

        return redirect()
            ->route('admin.testimonials.edit', $testimonial)
            ->with('success', 'Testimonial created.');
    }

    public function edit(Testimonial $testimonial): Response
    {
        $this->authorize('update', $testimonial);

        return Inertia::render('Testimonials/Admin/Testimonials/Edit', [
            'testimonial' => $testimonial,
        ]);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial)
    {
        $testimonial->update($request->validated());

        return back()->with('success', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $this->authorize('delete', $testimonial);

        $testimonial->delete();

        return redirect()
            ->route('admin.testimonials.index')
            ->with('success', 'Testimonial deleted.');
    }
}
