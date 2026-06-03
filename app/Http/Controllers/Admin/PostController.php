<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::with(['user:id,name', 'category:id,name'])
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['required', 'in:draft,published,archived'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['user_id'] = auth()->id();

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post = Post::create($validated);
        $post->tags()->sync($tags);

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully.');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', [
            'post' => $post->load('tags:id,name'),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug,' . $post->id],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['required', 'in:draft,published,archived'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        if ($validated['status'] === 'published' && ! $post->published_at) {
            $validated['published_at'] = now();
        }

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post->update($validated);
        $post->tags()->sync($tags);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully.');
    }
}
