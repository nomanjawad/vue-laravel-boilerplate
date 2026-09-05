<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Post;
use App\Models\Team;
use App\Modules\Core\ModuleManager;
use App\Modules\Faqs\Models\Faq;
use App\Modules\Testimonials\Models\Testimonial;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves collection-type widgets (testimonials, faqs, team, latest_posts)
 * into plain arrays keyed by widget id for the public DynamicPage.
 *
 * Guarded by SchemaCache::hasTable + module-enabled checks so a disabled module
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
        if (! $this->modules->enabled('testimonials') || ! SchemaCache::hasTable('testimonials')) {
            return [];
        }

        $limit = max(1, (int) ($collection['limit'] ?? 6));
        $mode = (string) ($collection['mode'] ?? 'latest');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));

        $query = Testimonial::query()
            ->where('is_active', true)
            ->select(['id', 'title', 'body']);

        if ($mode === 'picked' && $ids !== []) {
            $rows = $query->whereIn('id', $ids)->get();

            return $this->orderByIds($rows, $ids, $limit)
                ->map(fn (Testimonial $t) => $this->serializeTestimonial($t))
                ->all();
        }

        return $query->latest('id')->limit($limit)->get()
            ->map(fn (Testimonial $t) => $this->serializeTestimonial($t))
            ->all();
    }

    /**
     * @return array{id: int, name: string, title: string, body: string, quote: string}
     */
    private function serializeTestimonial(Testimonial $t): array
    {
        $body = (string) ($t->body ?? '');

        return [
            'id' => $t->id,
            'name' => (string) $t->title,
            'title' => (string) $t->title,
            'body' => $body,
            'quote' => $body,
        ];
    }

    private function resolveFaqs(array $collection, ?string $pageSlug): array
    {
        if (! $this->modules->enabled('faqs') || ! SchemaCache::hasTable('faqs')) {
            return [];
        }

        if (! Schema::hasColumn('faqs', 'page_slug')) {
            return Faq::query()
                ->where('is_active', true)
                ->latest('id')
                ->limit(max(1, (int) ($collection['limit'] ?? 20)))
                ->get(['id', 'title', 'body'])
                ->map(fn (Faq $f) => $this->serializeFaq($f))
                ->all();
        }

        $limit = max(1, (int) ($collection['limit'] ?? 20));
        $mode = (string) ($collection['mode'] ?? 'current_page');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));
        $pickedSlug = is_string($collection['page_slug'] ?? null) ? $collection['page_slug'] : null;

        if ($mode === 'picked' && $ids !== []) {
            $rows = Faq::query()
                ->where('is_active', true)
                ->whereIn('id', $ids)
                ->get(['id', 'title', 'body', 'page_slug']);

            return $this->orderByIds($rows, $ids, $limit)
                ->map(fn (Faq $f) => $this->serializeFaq($f))
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
                ->get(['id', 'title', 'body', 'page_slug'])
                ->map(fn (Faq $f) => $this->serializeFaq($f))
                ->all();
        }

        $items = Faq::query()
            ->where('is_active', true)
            ->forPageSlug($targetSlug)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'title', 'body', 'page_slug']);

        if ($items->isEmpty()) {
            $items = Faq::query()
                ->where('is_active', true)
                ->global()
                ->orderBy('id')
                ->limit($limit)
                ->get(['id', 'title', 'body', 'page_slug']);
        }

        return $items->map(fn (Faq $f) => $this->serializeFaq($f))->all();
    }

    /**
     * Public FAQ widgets + FAQPage schema expect question/answer; the Faq
     * model stores title/body.
     *
     * @return array{id: int, question: string, answer: string, page_slug: ?string}
     */
    private function serializeFaq(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'question' => (string) $faq->title,
            'answer' => (string) $faq->body,
            'page_slug' => $faq->page_slug ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return list<array<string, mixed>>
     */
    private function resolveTeam(array $collection): array
    {
        if (! $this->modules->enabled('teams') || ! SchemaCache::hasTable('teams')) {
            return [];
        }

        $limit = max(1, (int) ($collection['limit'] ?? 8));
        $mode = (string) ($collection['mode'] ?? 'latest');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));

        $query = Team::query()
            ->active()
            ->select(['id', 'name', 'position', 'bio', 'photo', 'sort_order']);

        if ($mode === 'picked' && $ids !== []) {
            $rows = $this->orderByIds($query->whereIn('id', $ids)->get(), $ids, $limit);
        } else {
            $rows = $query->orderBy('sort_order')->limit($limit)->get();
        }

        $map = Media::imagePayloadMap($rows->pluck('photo'));

        return $rows->map(function (Team $t) use ($map) {
            $row = $t->toArray();
            $key = is_string($t->photo) ? $t->photo : '';
            $row['photo'] = $map[$key] ?? Media::imagePayload($key !== '' ? $key : null);

            return $row;
        })->all();
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return list<array<string, mixed>>
     */
    private function resolvePosts(array $collection): array
    {
        if (! $this->modules->enabled('blog') || ! SchemaCache::hasTable('posts')) {
            return [];
        }

        $limit = max(1, (int) ($collection['limit'] ?? 3));
        $mode = (string) ($collection['mode'] ?? 'latest');
        $ids = array_values(array_filter((array) ($collection['ids'] ?? []), 'is_numeric'));
        $columns = ['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at'];

        $query = Post::query()->published()->select($columns);

        if ($mode === 'picked' && $ids !== []) {
            $rows = $this->orderByIds($query->whereIn('id', $ids)->get(), $ids, $limit);
        } else {
            $rows = $query->latest('published_at')->limit($limit)->get();
        }

        $map = Media::imagePayloadMap($rows->pluck('featured_image'));

        return $rows->map(function (Post $p) use ($map) {
            $row = $p->toArray();
            $key = $p->featured_image ?? '';
            $row['featured_image'] = $map[$key] ?? Media::imagePayload($p->featured_image);

            return $row;
        })->all();
    }

    /**
     * Preserve admin pick order and honor limit (F11 #40).
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     * @param  Collection<int, T>  $rows
     * @param  list<int|string>  $ids
     * @return Collection<int, T>
     */
    private function orderByIds(Collection $rows, array $ids, int $limit): Collection
    {
        $byId = $rows->keyBy(fn ($row) => (int) $row->getKey());
        $ordered = collect();

        foreach ($ids as $id) {
            $row = $byId->get((int) $id);
            if ($row !== null) {
                $ordered->push($row);
            }
            if ($ordered->count() >= $limit) {
                break;
            }
        }

        return $ordered;
    }
}
