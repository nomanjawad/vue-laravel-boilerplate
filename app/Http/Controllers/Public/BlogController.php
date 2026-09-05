<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Services\SeoService;
use App\Support\LcpPreload;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function __construct(private SeoService $seo) {}

    public function index(Request $request)
    {
        $paginator = Post::published()
            ->with(['user:id,name', 'categories:id,name,slug'])
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->category, fn ($q, $c) => $q->whereHas('categories', fn ($cq) => $cq->where('slug', $c)))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $map = Media::imagePayloadMap($paginator->getCollection()->pluck('featured_image'));

        return Inertia::render('Public/Blog/Index', [
            'posts' => $paginator->through(fn (Post $p) => $this->postListPayload($p, $map)),
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

        $paginator = Post::published()
            ->with(['user:id,name', 'categories:id,name,slug'])
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $map = Media::imagePayloadMap($paginator->getCollection()->pluck('featured_image'));

        return Inertia::render('Public/Blog/Index', [
            'posts' => $paginator->through(fn (Post $p) => $this->postListPayload($p, $map)),
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

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($q) => $q->whereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $categoryIds))
            )
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at']);

        $payloadMap = Media::imagePayloadMap(
            collect([$post->featured_image])->merge($related->pluck('featured_image'))
        );

        $postPayload = $post->toArray();
        $postPayload['featured_image'] = $payloadMap[$post->featured_image ?? '']
            ?? Media::imagePayload($post->featured_image);

        return Inertia::render('Public/Blog/Show', [
            'post' => $postPayload,
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
            'relatedPosts' => $related->map(function (Post $p) use ($payloadMap) {
                $row = $p->toArray();
                $row['featured_image'] = $payloadMap[$p->featured_image ?? '']
                    ?? Media::imagePayload($p->featured_image);

                return $row;
            }),
            'lcpPreload' => LcpPreload::fromMedia(
                $postPayload['featured_image'] ?? null,
                '(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px',
            ),
        ]);
    }

    /**
     * @param  array<string, array{url: string, variants: ?array, width: ?int, height: ?int, alt_text: ?string}|string>  $map
     * @return array<string, mixed>
     */
    private function postListPayload(Post $post, array $map = []): array
    {
        $row = $post->toArray();
        $key = $post->featured_image ?? '';
        $row['featured_image'] = $map[$key] ?? Media::imagePayload($post->featured_image);

        return $row;
    }
}
