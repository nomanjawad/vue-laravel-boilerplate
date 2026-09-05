<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * CRUD for JSON-backed pages at data/pages/{slug}.json.
 * Replaces the Pages half of PageContentController; layout (header/footer)
 * stays on PageContentController.
 */
class PageController extends Controller
{
    /** Slugs that must not be created/renamed into (reserved system routes). */
    private const RESERVED_SLUGS = [
        'admin', 'login', 'logout', 'register', 'password', 'email',
        'profile', 'blog', 'careers', 'case-studies', 'newsletter',
        'sitemap.xml', 'robots.txt', 'up', 'storage',
    ];

    public function __construct(private JsonDataService $jsonData) {}

    public function index()
    {
        $slugs = $this->jsonData->list('pages');
        $pages = [];

        foreach ($slugs as $slug) {
            $data = $this->jsonData->get("pages/{$slug}");
            $path = base_path("data/pages/{$slug}.json");
            $pages[] = [
                'slug' => $slug,
                'title' => $data['title'] ?? $slug,
                'status' => $data['status'] ?? 'draft',
                'updated_at' => is_file($path) ? date('c', filemtime($path)) : null,
            ];
        }

        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pages/Edit', [
            'page' => null,
            'widgetsRegistry' => array_values(config('widgets', [])),
            'pages' => $this->pageOptions(),
            'isCreate' => true,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'status' => ['required', 'in:published,draft'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'seo' => ['nullable', 'array'],
            'widgets' => ['nullable', 'array'],
        ]);

        $slug = $validated['slug'];
        $this->assertSlugAvailable($slug);

        $this->validateWidgets($validated['widgets'] ?? []);
        $seo = is_array($validated['seo'] ?? null) ? $validated['seo'] : [];
        $this->validateJsonLd($seo['json_ld'] ?? null);

        $payload = $this->buildPayload($validated);
        $this->jsonData->put("pages/{$slug}", $payload);

        activity('pages')
            ->causedBy(auth()->user())
            ->withProperties(['slug' => $slug, 'title' => $validated['title']])
            ->log('created page "'.$validated['title'].'"');

        return redirect("/admin/pages/{$slug}/edit")
            ->with('success', 'Page created.');
    }

    public function edit(string $slug)
    {
        $page = $this->jsonData->get("pages/{$slug}");
        abort_if($page === [], 404);

        return Inertia::render('Admin/Pages/Edit', [
            'page' => [
                'slug' => $slug,
                'title' => $page['title'] ?? $slug,
                'status' => $page['status'] ?? 'draft',
                'featured_image' => (string) ($page['featured_image'] ?? ($page['seo']['og_image'] ?? '')),
                'seo' => [
                    'title' => (string) (($page['seo']['title'] ?? '')),
                    'description' => (string) (($page['seo']['description'] ?? '')),
                    'noindex' => (bool) ($page['seo']['noindex'] ?? false),
                    'json_ld' => (string) (($page['seo']['json_ld'] ?? '')),
                ],
                'widgets' => $page['widgets'] ?? [],
            ],
            'widgetsRegistry' => array_values(config('widgets', [])),
            'pages' => $this->pageOptions(),
            'isCreate' => false,
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $existing = $this->jsonData->get("pages/{$slug}");
        abort_if($existing === [], 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'status' => ['required', 'in:published,draft'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'seo' => ['nullable', 'array'],
            'widgets' => ['nullable', 'array'],
        ]);

        $newSlug = $validated['slug'];
        if ($newSlug !== $slug) {
            $this->assertSlugAvailable($newSlug, $slug);
        }

        $this->validateWidgets($validated['widgets'] ?? []);
        $seo = is_array($validated['seo'] ?? null) ? $validated['seo'] : [];
        $this->validateJsonLd($seo['json_ld'] ?? null);

        $payload = $this->buildPayload($validated);

        if ($newSlug !== $slug) {
            $this->jsonData->put("pages/{$newSlug}", $payload);
            $this->jsonData->delete("pages/{$slug}");
            $this->redirectOldPageSlug($slug, $newSlug);
            activity('pages')
                ->causedBy(auth()->user())
                ->withProperties([
                    'slug' => $newSlug,
                    'old_slug' => $slug,
                    'title' => $validated['title'],
                ])
                ->log('renamed page "'.$slug.'" → "'.$newSlug.'"');
        } else {
            $this->jsonData->put("pages/{$slug}", $payload);
            activity('pages')
                ->causedBy(auth()->user())
                ->withProperties(['slug' => $slug, 'title' => $validated['title']])
                ->log('updated page "'.$validated['title'].'"');
        }

        return redirect("/admin/pages/{$newSlug}/edit")
            ->with('success', 'Page saved.');
    }

    public function destroy(string $slug)
    {
        $page = $this->jsonData->get("pages/{$slug}");
        abort_if($page === [], 404);

        // Protect the home page from accidental deletion.
        if ($slug === 'home') {
            throw ValidationException::withMessages([
                'slug' => 'The home page cannot be deleted.',
            ]);
        }

        $this->jsonData->delete("pages/{$slug}");

        activity('pages')
            ->causedBy(auth()->user())
            ->withProperties([
                'slug' => $slug,
                'title' => is_string($page['title'] ?? null) ? $page['title'] : $slug,
            ])
            ->log('deleted page "'.(is_string($page['title'] ?? null) ? $page['title'] : $slug).'"');

        return redirect('/admin/pages')->with('success', 'Page deleted.');
    }

    /** @param  array<string, mixed>  $validated */
    private function buildPayload(array $validated): array
    {
        $seo = is_array($validated['seo'] ?? null) ? $validated['seo'] : [];

        return [
            'title' => $validated['title'],
            'status' => $validated['status'],
            'featured_image' => (string) ($validated['featured_image'] ?? ''),
            'seo' => [
                'title' => (string) ($seo['title'] ?? ''),
                'description' => (string) ($seo['description'] ?? ''),
                'noindex' => (bool) ($seo['noindex'] ?? false),
                'json_ld' => (string) ($seo['json_ld'] ?? ''),
            ],
            'widgets' => array_values($validated['widgets'] ?? []),
        ];
    }

    private function assertSlugAvailable(string $slug, ?string $except = null): void
    {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            throw ValidationException::withMessages([
                'slug' => "The slug \"{$slug}\" is reserved.",
            ]);
        }

        $existing = $this->jsonData->list('pages');
        if (in_array($slug, $existing, true) && $slug !== $except) {
            throw ValidationException::withMessages([
                'slug' => 'A page with this slug already exists.',
            ]);
        }

        // Soft check: URI already claimed by a named route (blog, etc.).
        $uri = $slug;
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if ($route->uri() === $uri || $route->uri() === '/'.$uri) {
                $name = $route->getName();
                // Allow overwriting our own dynamic catch-all / page.show / home.
                if (in_array($name, ['page.show', 'home'], true)) {
                    continue;
                }
                throw ValidationException::withMessages([
                    'slug' => "The path /{$slug} is already claimed by another route.",
                ]);
            }
        }
    }

    /** @param  list<mixed>  $widgets */
    private function validateWidgets(array $widgets): void
    {
        $registry = config('widgets', []);

        foreach ($widgets as $i => $widget) {
            if (! is_array($widget)) {
                throw ValidationException::withMessages([
                    "widgets.{$i}" => 'Each widget must be an object.',
                ]);
            }

            $type = $widget['type'] ?? null;
            if (! is_string($type) || ! isset($registry[$type])) {
                throw ValidationException::withMessages([
                    "widgets.{$i}.type" => "Unknown widget type: {$type}",
                ]);
            }

            $id = $widget['id'] ?? null;
            if (! is_string($id) || $id === '') {
                throw ValidationException::withMessages([
                    "widgets.{$i}.id" => 'Each widget needs an id.',
                ]);
            }

            $knownKeys = collect($registry[$type]['fields'] ?? [])->pluck('key')->all();
            $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];

            foreach (array_keys($data) as $fieldKey) {
                if (! in_array($fieldKey, $knownKeys, true)) {
                    throw ValidationException::withMessages([
                        "widgets.{$i}.data.{$fieldKey}" => "Unknown field \"{$fieldKey}\" for widget type {$type}.",
                    ]);
                }
            }
        }
    }

    private function validateJsonLd(mixed $jsonLd): void
    {
        if (! is_string($jsonLd) || $jsonLd === '') {
            return;
        }

        json_decode($jsonLd);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'seo.json_ld' => 'The JSON-LD schema is not valid JSON: '.json_last_error_msg(),
            ]);
        }
    }

    private function redirectOldPageSlug(string $oldSlug, string $newSlug): void
    {
        $from = Redirect::normalizePath($oldSlug === 'home' ? '/' : "/{$oldSlug}");
        $to = Redirect::normalizePath($newSlug === 'home' ? '/' : "/{$newSlug}");

        Redirect::updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301, 'is_active' => true],
        );

        Redirect::where('to_path', $from)->update(['to_path' => $to]);
        Redirect::where('from_path', $to)->where('to_path', $to)->delete();
    }

    /**
     * @return list<array{slug: string, title: string}>
     */
    private function pageOptions(): array
    {
        return collect($this->jsonData->list('pages'))->map(function (string $slug) {
            $data = $this->jsonData->get("pages/{$slug}");

            return [
                'slug' => $slug,
                'title' => is_string($data['title'] ?? null) && $data['title'] !== ''
                    ? $data['title']
                    : $slug,
            ];
        })->values()->all();
    }
}
