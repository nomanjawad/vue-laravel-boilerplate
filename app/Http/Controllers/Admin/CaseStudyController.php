<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CaseStudyController extends Controller
{
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

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

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
            'slug' => ['nullable', 'string', 'max:255', 'unique:case_studies,slug,' . $caseStudy->id],
            'client_name' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $caseStudy->update($validated);

        return redirect()->route('admin.case-studies.index')->with('success', 'Case study updated successfully.');
    }

    public function destroy(CaseStudy $caseStudy)
    {
        $caseStudy->delete();
        return redirect()->route('admin.case-studies.index')->with('success', 'Case study deleted successfully.');
    }
}
