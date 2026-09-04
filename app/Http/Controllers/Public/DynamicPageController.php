<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\JsonDataService;
use App\Services\SeoService;
use App\Services\WidgetDataResolver;
use Inertia\Inertia;

class DynamicPageController extends Controller
{
    public function __construct(
        private JsonDataService $jsonData,
        private WidgetDataResolver $widgetData,
        private SeoService $seo,
    ) {}

    public function show(string $slug = 'home')
    {
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            abort(404);
        }

        $page = $this->jsonData->get("pages/{$slug}");

        if ($page === [] || ($page['status'] ?? '') !== 'published') {
            abort(404);
        }

        $widgets = is_array($page['widgets'] ?? null) ? $page['widgets'] : [];
        $collectionData = $this->widgetData->resolve($widgets, $slug);
        $title = (string) ($page['title'] ?? $slug);

        $crumbs = $slug === 'home'
            ? [['name' => 'Home', 'url' => '/']]
            : [
                ['name' => 'Home', 'url' => '/'],
                ['name' => $title, 'url' => "/{$slug}"],
            ];

        $jsonLd = [$this->seo->breadcrumbs($crumbs)];

        // FAQPage schema from every visible faqs widget that has resolved items.
        foreach ($widgets as $widget) {
            if (($widget['type'] ?? '') !== 'faqs' || ($widget['visible'] ?? true) === false) {
                continue;
            }
            $id = $widget['id'] ?? null;
            $items = is_string($id) ? ($collectionData[$id] ?? []) : [];
            if (! is_array($items) || $items === []) {
                continue;
            }
            $faqSchema = $this->seo->faqPage($items);
            if ($faqSchema) {
                $jsonLd[] = $faqSchema;
            }
        }

        return Inertia::render('Public/DynamicPage', [
            'page' => [
                'title' => $title,
                'slug' => $slug,
                'widgets' => $widgets,
            ],
            'collectionData' => $collectionData,
            'breadcrumbs' => $crumbs,
            'jsonLd' => $jsonLd,
            'lcpPreload' => $this->resolveLcpPreload($widgets),
        ]);
    }

    /**
     * When the first visible widget is an image-bearing hero (or image
     * widget), return a URL suitable for <link rel="preload" as="image">.
     * Prefer the md variant (1200w) when present — close to typical LCP size.
     */
    private function resolveLcpPreload(array $widgets): ?string
    {
        foreach ($widgets as $widget) {
            if (($widget['visible'] ?? true) === false) {
                continue;
            }

            $type = $widget['type'] ?? '';
            $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];

            $raw = match ($type) {
                'hero' => $data['image'] ?? null,
                'image' => $data['src'] ?? null,
                default => null,
            };

            return $this->lcpUrlFromMedia($raw);
        }

        return null;
    }

    private function lcpUrlFromMedia(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Media object from the page editor (Phase 10): prefer md variant.
        if (is_array($raw)) {
            $variants = is_array($raw['variants'] ?? null) ? $raw['variants'] : [];
            $mdPath = Media::variantPath($variants['md'] ?? null);
            if ($mdPath) {
                return $this->imageUrl($mdPath);
            }
            $url = $raw['url'] ?? null;

            return is_string($url) && $url !== '' ? $this->imageUrl($url) : null;
        }

        if (is_string($raw)) {
            return $this->imageUrl($raw);
        }

        return null;
    }
}
