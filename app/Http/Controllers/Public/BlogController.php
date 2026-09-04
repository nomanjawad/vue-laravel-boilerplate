<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function __construct(private SeoService $seo) {}

    public function index(Request $request)
    {
        return Inertia::render('Public/Blog/Index', [
            'posts' => Post::published()
                ->with(['user:id,name', 'categories:id,name,slug'])
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->when($request->category, fn ($q, $c) => $q->whereHas('categories', fn ($cq) => $cq->where('slug', $c)))
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => Category::withCount(['posts' => fn ($q) => $q->published()])
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'filters' => $request->only('search', 'category'),
            'archive' => null,
        ]);
    }

    public function category(Request $request, Category $category)
    {
        $ids = $category->selfAndDescendantIds();

        return Inertia::render('Public/Blog/Index', [
            'posts' => Post::published()
                ->with(['user:id,name', 'categories:id,name,slug'])
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids))
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => Category::withCount(['posts' => fn ($q) => $q->published()])
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $request->search,
                'category' => $category->slug,
            ],
            'archive' => [
                'name' => $category->name,
                'description' => $category->description,
                'slug' => $category->slug,
            ],
        ]);
    }

    public function show(Request $request, Post $post)
    {
        // Drafts are visible only through a valid signed preview link
        // (generated from the admin post editor).
        if ($post->status !== 'published' && ! $request->hasValidSignature()) {
            abort(404);
        }

        $post->load(['user:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);
        $categoryIds = $post->categories->pluck('id');

        return Inertia::render('Public/Blog/Show', [
            'post' => $post,
            'isPreview' => $post->status !== 'published',
            'jsonLd' => [
                $this->seo->blogPosting($post),
                $this->seo->breadcrumbs([
                    ['name' => 'Home', 'url' => '/'],
                    ['name' => 'Blog', 'url' => '/blog'],
                    ['name' => $post->title, 'url' => "/blog/{$post->slug}"],
                ]),
            ],
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Blog', 'url' => '/blog'],
                ['name' => $post->title, 'url' => "/blog/{$post->slug}"],
            ],
            'relatedPosts' => Post::published()
                ->where('id', '!=', $post->id)
                ->when(
                    $categoryIds->isNotEmpty(),
                    fn ($q) => $q->whereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $categoryIds))
                )
                ->latest('published_at')
                ->take(3)
                ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at']),
        ]);
    }
}
