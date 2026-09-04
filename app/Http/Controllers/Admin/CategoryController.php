<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('posts')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Category::create($validated);

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        // Categories used to be assignable as their own parent, which
        // silently creates a cycle that breaks every recursive tree walk
        // (breadcrumbs, menus). Reject self-reference and any descendant.
        $descendantIds = $category->descendantIds();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:categories,slug,'.$category->id,
                // Uncategorized keeps its reserved slug (WP parity).
                Rule::when($category->isUncategorized(), Rule::in([Category::UNCATEGORIZED_SLUG])),
            ],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id, ...$descendantIds]),
                Rule::prohibitedIf($category->isUncategorized()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($category->isUncategorized()) {
            $validated['slug'] = Category::UNCATEGORIZED_SLUG;
            $validated['parent_id'] = null;
        } else {
            $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        }

        $validated['sort_order'] = $validated['sort_order'] ?? $category->sort_order;

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->isUncategorized()) {
            return back()->with('error', 'The Uncategorized category cannot be deleted.');
        }

        // WP behavior: children move up one level; posts left without a
        // category fall back to Uncategorized.
        $affectedPostIds = $category->posts()->pluck('posts.id')->all();
        $parentId = $category->parent_id;

        Category::where('parent_id', $category->id)->update(['parent_id' => $parentId]);

        $category->delete();

        if ($affectedPostIds !== []) {
            $uncategorizedId = Category::uncategorized()->id;
            $orphans = Post::whereIn('id', $affectedPostIds)
                ->whereDoesntHave('categories')
                ->get();

            foreach ($orphans as $post) {
                $post->categories()->attach($uncategorizedId);
            }
        }

        return back()->with('success', 'Category deleted successfully.');
    }
}
