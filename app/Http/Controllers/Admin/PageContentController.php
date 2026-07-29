<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Edits `data/*.json` (JsonDataService) — content stays in JSON, never the
 * DB. `pages` carry an SEO block inside their JSON; `header`/`footer` are
 * layout-only and have none.
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
            'pages' => collect(self::PAGE_FILES)->map(fn (string $label, string $file) => [
                'file' => $file,
                'label' => $label,
                'data' => $this->jsonData->get($file),
            ])->values(),
            'layout' => collect(self::LAYOUT_FILES)->map(fn (string $label, string $file) => [
                'file' => $file,
                'label' => $label,
                'data' => $this->jsonData->get($file),
            ])->values(),
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

        $this->jsonData->put($file, $validated['content']);

        return back()->with('success', 'Content updated successfully.');
    }
}
