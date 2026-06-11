<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CaseStudyController extends Controller
{
    public function __construct(private SlugService $slugs) {}

    public function index(Request $request)
    {
        return Inertia::render('Admin/CaseStudies/Index', [
            'caseStudies' => CaseStudy::when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->orderBy('sort_order')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CaseStudies/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:case_studies'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['slug'] = $this->slugs->generate(new CaseStudy, $validated['slug'] ?: $validated['title']);

        CaseStudy::create($validated);

        return redirect()->route('admin.case-studies.index')->with('success', 'Case study created successfully.');
    }

    public function edit(CaseStudy $caseStudy)
    {
        return Inertia::render('Admin/CaseStudies/Edit', [
            'caseStudy' => $caseStudy,
        ]);
    }

    public function update(Request $request, CaseStudy $caseStudy)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:case_studies,slug,'.$caseStudy->id],
            'client_name' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        // Changing a slug breaks published links — auto-create a 301 from the old URL.
        $oldSlug = $caseStudy->slug;
        $validated['slug'] = ($validated['slug'] ?? null) ?: $oldSlug;

        $caseStudy->update($validated);

        if ($caseStudy->is_active) {
            $this->slugs->redirectOldSlug('case-studies', $oldSlug, $caseStudy->slug);
        }

        return redirect()->route('admin.case-studies.index')->with('success', 'Case study updated successfully.');
    }

    public function destroy(CaseStudy $caseStudy)
    {
        $caseStudy->delete();

        return redirect()->route('admin.case-studies.index')->with('success', 'Case study deleted successfully.');
    }
}
