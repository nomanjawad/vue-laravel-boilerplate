<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Public/Blog/Index', [
            'posts' => Post::published()
                ->with(['user:id,name', 'category:id,name,slug'])
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->when($request->category, fn ($q, $c) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $c)))
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => Category::withCount(['posts' => fn ($q) => $q->published()])->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $request->only('search', 'category'),
        ]);
    }

    public function show(Post $post)
    {
        if ($post->status !== 'published') {
            abort(404);
        }

        return Inertia::render('Public/Blog/Show', [
            'post' => $post->load(['user:id,name', 'category:id,name,slug', 'tags:id,name,slug']),
            'relatedPosts' => Post::published()
                ->where('id', '!=', $post->id)
                ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
                ->latest('published_at')
                ->take(3)
                ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at']),
        ]);
    }
}
