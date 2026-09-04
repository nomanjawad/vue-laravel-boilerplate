<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Report (and optionally delete) orphaned media files and broken Media rows.
 *
 *   php artisan media:prune          # dry-run report
 *   php artisan media:prune --delete # remove orphans + broken rows
 */
class MediaPrune extends Command
{
    protected $signature = 'media:prune {--delete : Delete orphaned files and rows whose files are missing}';

    protected $description = 'Find media files on disk with no Media row, and Media rows whose file is missing';

    public function handle(MediaService $mediaService): int
    {
        $disk = Storage::disk('public');
        $delete = (bool) $this->option('delete');

        $knownPaths = [];
        Media::query()->each(function (Media $media) use (&$knownPaths) {
            $knownPaths[$media->path] = true;
            foreach ($media->variantPaths() as $variantPath) {
                $knownPaths[$variantPath] = true;
            }
        });

        $orphanFiles = [];
        foreach ($disk->allFiles('media') as $path) {
            if (! isset($knownPaths[$path])) {
                $orphanFiles[] = $path;
            }
        }

        $missingRows = Media::query()->get()->filter(function (Media $media) use ($disk) {
            return ! $disk->exists($media->path);
        });

        $this->info('Orphan files (on disk, no Media row): '.count($orphanFiles));
        foreach ($orphanFiles as $path) {
            $this->line("  - {$path}");
            if ($delete) {
                $disk->delete($path);
            }
        }

        $this->info('Broken rows (Media row, file missing): '.$missingRows->count());
        foreach ($missingRows as $media) {
            $this->line("  - #{$media->id} {$media->path}");
            if ($delete) {
                // File already gone; still clean variants + DB row via service.
                $mediaService->delete($media);
            }
        }

        if (! $delete && (count($orphanFiles) || $missingRows->isNotEmpty())) {
            $this->newLine();
            $this->comment('Dry run only. Re-run with --delete to remove.');
        }

        return self::SUCCESS;
    }
}
