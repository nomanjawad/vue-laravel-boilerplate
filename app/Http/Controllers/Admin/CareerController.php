<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CareerController extends Controller
{
    public function __construct(private SlugService $slugs) {}

    public function index(Request $request)
    {
        return Inertia::render('Admin/Careers/Index', [
            'careers' => Career::when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->orderBy('sort_order')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Careers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:careers'],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:full-time,part-time,contract,remote'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['slug'] = $this->slugs->generate(new Career, $validated['slug'] ?: $validated['title']);

        Career::create($validated);

        return redirect()->route('admin.careers.index')->with('success', 'Career created successfully.');
    }

    public function edit(Career $career)
    {
        return Inertia::render('Admin/Careers/Edit', [
            'career' => $career,
        ]);
    }

    public function update(Request $request, Career $career)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:careers,slug,'.$career->id],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:full-time,part-time,contract,remote'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        // Changing a slug breaks published links — auto-create a 301 from the old URL.
        $oldSlug = $career->slug;
        $validated['slug'] = ($validated['slug'] ?? null) ?: $oldSlug;

        $career->update($validated);

        if ($career->is_active) {
            $this->slugs->redirectOldSlug('careers', $oldSlug, $career->slug);
        }

        return redirect()->route('admin.careers.index')->with('success', 'Career updated successfully.');
    }

    public function destroy(Career $career)
    {
        $career->delete();

        return redirect()->route('admin.careers.index')->with('success', 'Career deleted successfully.');
    }
}
