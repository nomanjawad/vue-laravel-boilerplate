<?php

namespace App\Services;

use App\Models\CustomCode;
use Illuminate\Support\Facades\Schema;

/**
 * Renders admin-authored code snippets for resources/views/app.blade.php.
 * Three placements, matching the actual document structure — not the Vue
 * <Head> component, since these need to exist in the server-rendered HTML
 * that loads before the SPA hydrates (third-party trackers, verification
 * meta tags, etc. expect that).
 */
class CustomCodeService
{
    public function render(string $placement): string
    {
        return $this->renderAll()[$placement] ?? '';
    }

    /**
     * All three placements in one query — app.blade.php renders each of
     * these on every request, so this stays a single round trip rather than
     * three.
     *
     * @return array{head: string, body_start: string, body_end: string}
     */
    public function renderAll(): array
    {
        $placements = ['head', 'body_start', 'body_end'];

        // Guards a fresh install / mid-deploy request where migrations
        // haven't run yet — app.blade.php renders on every request, so this
        // can't throw.
        if (! Schema::hasTable('custom_codes')) {
            return array_fill_keys($placements, '');
        }

        $grouped = CustomCode::query()
            ->active()
            ->orderBy('sort_order')
            ->get(['placement', 'code'])
            ->groupBy('placement');

        return collect($placements)
            ->mapWithKeys(fn (string $p) => [$p => $grouped->get($p, collect())->pluck('code')->implode("\n")])
            ->all();
    }
}
