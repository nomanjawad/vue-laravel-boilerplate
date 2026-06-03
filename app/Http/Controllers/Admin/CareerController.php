<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CareerController extends Controller
{
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

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

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
            'slug' => ['nullable', 'string', 'max:255', 'unique:careers,slug,' . $career->id],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:full-time,part-time,contract,remote'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $career->update($validated);

        return redirect()->route('admin.careers.index')->with('success', 'Career updated successfully.');
    }

    public function destroy(Career $career)
    {
        $career->delete();
        return redirect()->route('admin.careers.index')->with('success', 'Career deleted successfully.');
    }
}
