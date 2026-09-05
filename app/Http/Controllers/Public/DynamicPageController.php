<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\JsonDataService;
use App\Services\SeoService;
use App\Services\WidgetDataResolver;
use App\Support\LcpPreload;
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
     * Prefetch the first image-bearing widget so LCP matches AppImage's
     * srcset selection (F12 #2). Scan the first few visible widgets — pages
     * that open with rich_text/cta before a hero still get a preload.
     *
     * @return array{href: string, imagesrcset?: string, imagesizes?: string}|null
     */
    private function resolveLcpPreload(array $widgets): ?array
    {
        $scanned = 0;

        foreach ($widgets as $widget) {
            if (($widget['visible'] ?? true) === false) {
                continue;
            }

            $scanned++;
            if ($scanned > 3) {
                break;
            }

            $type = $widget['type'] ?? '';
            $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];

            $raw = match ($type) {
                'hero' => $data['image'] ?? null,
                'image' => $data['src'] ?? null,
                default => null,
            };

            $preload = LcpPreload::fromMedia(
                $raw,
                $type === 'hero' ? '100vw' : '(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px',
            );
            if ($preload !== null) {
                return $preload;
            }
        }

        return null;
    }
}
