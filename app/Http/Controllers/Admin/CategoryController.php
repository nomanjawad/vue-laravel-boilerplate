<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('posts')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Categories/Create', [
            'parentCategories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
            'parentCategories' => Category::where('id', '!=', $category->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        // Categories used to be assignable as their own parent, which
        // silently creates a cycle that breaks every recursive tree walk
        // (breadcrumbs, menus). Reject self-reference and any descendant.
        $descendantIds = $this->descendantIds($category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id, ...$descendantIds]),
            ],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * IDs of $category and all its descendants — none of which may be set
     * as its `parent_id` (would create a cycle).
     *
     * @return array<int, int>
     */
    private function descendantIds(Category $category): array
    {
        $ids = [];
        $frontier = [$category->id];
        while ($frontier !== []) {
            $children = Category::whereIn('parent_id', $frontier)->pluck('id')->all();
            if ($children === []) {
                break;
            }
            $ids = [...$ids, ...$children];
            $frontier = $children;
        }

        return $ids;
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
