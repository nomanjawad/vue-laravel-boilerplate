<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Edits layout JSON only (header.json / footer.json). Page CRUD lives on
 * Admin\PageController (data/pages/{slug}.json).
 */
class PageContentController extends Controller
{
    private const LAYOUT_FILES = [
        'header' => 'Header',
        'footer' => 'Footer',
    ];

    public function __construct(private JsonDataService $jsonData) {}

    public function layout()
    {
        return Inertia::render('Admin/PageContent/Layout', [
            'layout' => $this->filesPayload(self::LAYOUT_FILES),
        ]);
    }

    public function update(Request $request, string $file)
    {
        abort_unless(array_key_exists($file, self::LAYOUT_FILES), 404);

        $validated = $request->validate([
            'content' => ['required', 'array'],
        ]);

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
