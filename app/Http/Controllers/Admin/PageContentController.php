<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Edits `data/*.json` (JsonDataService) — content stays in JSON, never the
 * DB. Pages carry an SEO block inside their JSON (title, description,
 * og_image, noindex, json_ld); header/footer are layout-only and have none.
 * Split into two admin nav entries — "Pages" (index) and "Header / Footer"
 * (layout) — each its own sidebar link rather than tabs on one screen.
 */
class PageContentController extends Controller
{
    private const PAGE_FILES = [
        'home' => 'Home',
        'about' => 'About',
        'contact' => 'Contact',
    ];

    private const LAYOUT_FILES = [
        'header' => 'Header',
        'footer' => 'Footer',
    ];

    public function __construct(private JsonDataService $jsonData) {}

    public function index()
    {
        return Inertia::render('Admin/PageContent/Index', [
            'pages' => $this->filesPayload(self::PAGE_FILES),
        ]);
    }

    public function layout()
    {
        return Inertia::render('Admin/PageContent/Layout', [
            'layout' => $this->filesPayload(self::LAYOUT_FILES),
        ]);
    }

    public function update(Request $request, string $file)
    {
        abort_unless(
            array_key_exists($file, self::PAGE_FILES) || array_key_exists($file, self::LAYOUT_FILES),
            404
        );

        $validated = $request->validate([
            'content' => ['required', 'array'],
        ]);

        $jsonLd = $validated['content']['seo']['json_ld'] ?? null;
        if (is_string($jsonLd) && $jsonLd !== '') {
            json_decode($jsonLd);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    'content.seo.json_ld' => 'The JSON-LD schema is not valid JSON: '.json_last_error_msg(),
                ]);
            }
        }

        $this->jsonData->put($file, $validated['content']);

        return back()->with('success', 'Content updated successfully.');
    }

    /** @param  array<string, string>  $files */
    private function filesPayload(array $files): array
    {
        return collect($files)->map(fn (string $label, string $file) => [
            'file' => $file,
            'label' => $label,
            'data' => $this->jsonData->get($file),
        ])->values()->all();
    }
}
