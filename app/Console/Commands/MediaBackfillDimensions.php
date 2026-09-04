<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Console\Command;

/**
 * Backfill width/height (+ per-variant dims in variants JSON) for existing
 * media rows that predate the Phase-10 dimensions columns.
 *
 *   php artisan media:backfill-dimensions
 */
class MediaBackfillDimensions extends Command
{
    protected $signature = 'media:backfill-dimensions';

    protected $description = 'Read image files and fill media width/height + variant dimensions';

    public function handle(MediaService $mediaService): int
    {
        $updated = 0;
        $skipped = 0;

        Media::query()->orderBy('id')->each(function (Media $media) use ($mediaService, &$updated, &$skipped) {
            if (! str_starts_with((string) $media->mime_type, 'image/')) {
                $skipped++;

                return;
            }

            if ($mediaService->backfillDimensions($media)) {
                $updated++;
                $this->line("  #{$media->id} {$media->filename} → {$media->width}×{$media->height}");
            } else {
                $skipped++;
            }
        });

        $this->info("Updated {$updated} row(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}
