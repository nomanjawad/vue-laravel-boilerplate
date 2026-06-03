<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageMetaController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/PageMetas/Index', [
            'pageMetas' => PageMeta::orderBy('route_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_name' => ['required', 'string', 'max:255', 'unique:page_metas'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255'],
        ]);

        PageMeta::create($validated);

        return back()->with('success', 'Page meta created successfully.');
    }

    public function update(Request $request, PageMeta $pageMeta)
    {
        $validated = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255'],
        ]);

        $pageMeta->update($validated);

        return back()->with('success', 'Page meta updated successfully.');
    }

    public function destroy(PageMeta $pageMeta)
    {
        $pageMeta->delete();

        return back()->with('success', 'Page meta deleted successfully.');
    }
}
