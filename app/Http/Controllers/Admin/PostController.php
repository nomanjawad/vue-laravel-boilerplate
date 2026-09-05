<?php

namespace App\Http\Controllers\Admin;

use App\Data\CategorySummaryData;
use App\Data\PostData;
use App\Data\TagSummaryData;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Modules\Core\ModuleManager;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class PostController extends Controller
{
    public function __construct(private SlugService $slugs, private ModuleManager $modules) {}

    public function index(Request $request)
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::with(['user:id,name', 'categories:id,name'])
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Post $post) => PostData::fromModel($post)),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Create', [
            'categories' => $this->categoryOptions(),
            'tags' => TagSummaryData::collect(
                Tag::orderBy('name')->get(['id', 'name', 'slug'])
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'status' => ['required', 'in:draft,published,archived'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:191'],
            'noindex' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        // $request->boolean(), not the validated value directly: a switch/
        // checkbox that's off may not send the key at all, and `boolean()`
        // treats a missing key as false instead of leaving the column null.
        $validated['noindex'] = $request->boolean('noindex');
        $validated['slug'] = $this->slugs->generate(new Post, $validated['slug'] ?: $validated['title']);
        $validated['user_id'] = auth()->id();

        $this->authorizePublish($validated['status']);

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $tags = $validated['tags'] ?? [];
        $categories = $validated['categories'] ?? [];
        unset($validated['tags'], $validated['categories']);

        $post = Post::create($validated);
        $post->tags()->sync($tags);
        $this->syncCategories($post, $categories);

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully.');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', [
            'post' => PostData::fromModel($post->load(['tags:id,name,slug', 'categories:id,name,slug,parent_id'])),
            // Signed link lets clients view drafts before publishing (valid 7 days).
            'previewUrl' => $this->modules->enabled('blog')
                ? URL::temporarySignedRoute('blog.show', now()->addDays(7), ['post' => $post->slug])
                : null,
            'categories' => $this->categoryOptions(),
            'tags' => TagSummaryData::collect(
                Tag::orderBy('name')->get(['id', 'name', 'slug'])
            ),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug,'.$post->id],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'status' => ['required', 'in:draft,published,archived'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:191'],
            'noindex' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $validated['noindex'] = $request->boolean('noindex');

        $this->authorizePublish($validated['status'], $post);

        if ($validated['status'] === 'published' && ! $post->published_at) {
            $validated['published_at'] = now();
        }

        $tags = $validated['tags'] ?? [];
        $categories = $validated['categories'] ?? [];
        unset($validated['tags'], $validated['categories']);

        // Changing a slug breaks published links — auto-create a 301 from the old URL.
        $oldSlug = $post->slug;
        $validated['slug'] = $validated['slug'] ?: $oldSlug;

        $post->update($validated);

        if ($post->status === 'published') {
            $this->slugs->redirectOldSlug('blog', $oldSlug, $post->slug);
        }
        $post->tags()->sync($tags);
        $this->syncCategories($post, $categories);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully.');
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function syncCategories(Post $post, array $categoryIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $categoryIds)));
        if ($ids === []) {
            $ids = [Category::uncategorized()->id];
        }
        $post->categories()->sync($ids);
    }

    /** Require posts.publish when moving a post into the published status. */
    private function authorizePublish(string $status, ?Post $existing = null): void
    {
        if ($status !== 'published') {
            return;
        }
        if ($existing && $existing->status === 'published') {
            return;
        }

        abort_unless(
            auth()->user()?->can('posts.publish'),
            403,
            'You do not have permission to publish posts.',
        );
    }

    /** @return \Illuminate\Support\Collection<int, CategorySummaryData> */
    private function categoryOptions()
    {
        return CategorySummaryData::collect(
            Category::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug', 'parent_id'])
        );
    }
}
