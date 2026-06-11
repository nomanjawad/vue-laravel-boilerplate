<?php

namespace App\Services;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Generate a unique slug for a model from a source string.
     * Appends -2, -3, ... when the base slug is taken.
     */
    public function generate(Model $model, string $source, string $column = 'slug'): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;

        while (
            $model->newQuery()
                ->where($column, $slug)
                ->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))
                ->exists()
        ) {
            $slug = "{$base}-".$i++;
        }

        return $slug;
    }

    /**
     * Call after changing a published model's slug: creates a 301 from the old
     * public URL to the new one so existing links and rankings survive.
     *
     * $publicPrefix is the public route segment, e.g. 'blog', 'shop', 'page'.
     */
    public function redirectOldSlug(string $publicPrefix, string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === $newSlug) {
            return;
        }

        $from = Redirect::normalizePath("{$publicPrefix}/{$oldSlug}");
        $to = Redirect::normalizePath("{$publicPrefix}/{$newSlug}");

        Redirect::updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301, 'is_active' => true],
        );

        // Re-point any older redirects that targeted the old URL (avoid chains),
        // and drop a redirect that would now loop back onto itself.
        Redirect::where('to_path', $from)->update(['to_path' => $to]);
        Redirect::where('from_path', $to)->where('to_path', $to)->delete();
    }
}
