<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Team;
use App\Modules\Core\ModuleManager;
use App\Modules\Faqs\Models\Faq;
use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves collection-type widgets (testimonials, faqs, team, latest_posts)
 * into plain arrays keyed by widget id for the public DynamicPage.
 *
 * Guarded by Schema::hasTable + module-enabled checks so a disabled module
 * or missing migration never 500s the page.
 */
class WidgetDataResolver
{
    public function __construct(private ModuleManager $modules) {}

    /**
     * @param  list<array{id?: string, type?: string, visible?: bool, data?: array}>  $widgets
     * @return array<string, list<array<string, mixed>>>
     */
    public function resolve(array $widgets, ?string $pageSlug = null): array
    {
        $out = [];

        foreach ($widgets as $widget) {
            if (! ($widget['visible'] ?? true)) {
                continue;
            }

            $id = $widget['id'] ?? null;
            $type = $widget['type'] ?? null;
            if (! is_string($id) || $id === '' || ! is_string($type)) {
                continue;
            }

            $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];
            $collection = is_array($data['collection'] ?? null) ? $data['collection'] : [];

            $items = match ($type) {
                'testimonials' => $this->resolveTestimonials($collection),
                'faqs' => $this->resolveFaqs($collection, $pageSlug),
                'team' => $this->resolveTeam($collection),
                'latest_posts' => $this->resolvePosts($collection),
                default => null,
            };

            if ($items !== null) {
                $out[$id] = $items;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return list<array<string, mixed>>
     */
    private function resolveTestimonials(array $collection): array
    {
        if (! $this->modules->enabled('testimonials') || ! Schema::hasTable('testimonials')) {
            return [];
        }

        $limit = max(1, (int) ($collection['limit'] ?? 6));
        $mode = (string) ($collection['mode'] ?? 'latest');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));

        $query = Testimonial::query()->where('is_active', true);

        if ($mode === 'picked' && $ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $query->latest('id')->limit($limit);
        }

        return $query->get()->map(fn (Testimonial $t) => $t->toArray())->all();
    }

    private function resolveFaqs(array $collection, ?string $pageSlug): array
    {
        if (! $this->modules->enabled('faqs') || ! Schema::hasTable('faqs')) {
            return [];
        }

        if (! Schema::hasColumn('faqs', 'page_slug')) {
            return Faq::query()
                ->where('is_active', true)
                ->latest('id')
                ->limit(max(1, (int) ($collection['limit'] ?? 20)))
                ->get()
                ->map(fn (Faq $f) => $f->toArray())
                ->all();
        }

        $limit = max(1, (int) ($collection['limit'] ?? 20));
        $mode = (string) ($collection['mode'] ?? 'current_page');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));
        $pickedSlug = is_string($collection['page_slug'] ?? null) ? $collection['page_slug'] : null;

        if ($mode === 'picked' && $ids !== []) {
            return Faq::query()
                ->where('is_active', true)
                ->whereIn('id', $ids)
                ->limit($limit)
                ->get()
                ->map(fn (Faq $f) => $f->toArray())
                ->all();
        }

        $targetSlug = match ($mode) {
            'picked' => $pickedSlug,
            'global' => null,
            default => $pageSlug ?: $pickedSlug, // current_page
        };

        if ($mode === 'global' || ! $targetSlug) {
            return Faq::query()
                ->where('is_active', true)
                ->global()
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map(fn (Faq $f) => $f->toArray())
                ->all();
        }

        $items = Faq::query()
            ->where('is_active', true)
            ->forPageSlug($targetSlug)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            $items = Faq::query()
                ->where('is_active', true)
                ->global()
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        return $items->map(fn (Faq $f) => $f->toArray())->all();
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return list<array<string, mixed>>
     */
    private function resolveTeam(array $collection): array
    {
        if (! $this->modules->enabled('teams') || ! Schema::hasTable('teams')) {
            return [];
        }

        $limit = max(1, (int) ($collection['limit'] ?? 8));
        $mode = (string) ($collection['mode'] ?? 'latest');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));

        $query = Team::query()->active();

        if ($mode === 'picked' && $ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $query->orderBy('sort_order')->limit($limit);
        }

        return $query->get()->map(fn (Team $t) => $t->toArray())->all();
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return list<array<string, mixed>>
     */
    private function resolvePosts(array $collection): array
    {
        if (! $this->modules->enabled('blog') || ! Schema::hasTable('posts')) {
            return [];
        }

        $limit = max(1, (int) ($collection['limit'] ?? 3));
        $mode = (string) ($collection['mode'] ?? 'latest');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));

        $query = Post::query()->published();

        if ($mode === 'picked' && $ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $query->latest('published_at')->limit($limit);
        }

        return $query
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at'])
            ->map(fn (Post $p) => $p->toArray())
            ->all();
    }
}
