<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Central upload pipeline. Images are re-encoded to WebP (which also strips
 * EXIF/GPS metadata) and resized into web-friendly variants so a 4MB camera
 * JPEG never reaches a public page. Non-images are stored untouched.
 *
 * Uses the GD driver — available on virtually every shared host.
 */
class MediaService
{
    /** Generated variants: name => max width in px. */
    private const VARIANTS = [
        'md' => 1200,
        'thumb' => 400,
    ];

    /** Cap the stored original so huge camera files never hit the disk. */
    private const MAX_ORIGINAL_WIDTH = 2000;

    private const OPTIMIZABLE = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Second-line defence: `MediaController` validates uploads, but
     * `importFromContents()` (used by WordPress import etc.) reaches this
     * method directly. Reject anything that isn't an image or a PDF so
     * .php/.phtml/.htaccess/.html files cannot land under public/storage.
     */
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'application/pdf',
    ];

    public function upload(UploadedFile $file, ?string $altText = null, ?int $userId = null): Media
    {
        $mime = $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException("Refusing to store file with MIME type [{$mime}].");
        }

        $dir = 'media/'.date('Y/m');
        $width = null;
        $height = null;

        if (in_array($mime, self::OPTIMIZABLE, true)) {
            [$path, $size, $variants, $width, $height] = $this->storeOptimizedImage($file, $dir);
            $mime = 'image/webp';
        } else {
            // Non-optimizable uploads (GIF, PDF, ...) are stored as-is.
            $path = $file->store($dir, 'public');
            $size = $file->getSize();
            $variants = null;

            if (str_starts_with((string) $mime, 'image/')) {
                [$width, $height] = $this->readDimensions($file->getRealPath());
            }
        }

        return Media::create([
            'user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText,
            'variants' => $variants,
        ]);
    }

    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $disk->delete($media->path);

        foreach ($media->variantPaths() as $variantPath) {
            $disk->delete($variantPath);
        }

        $media->delete();
    }

    /**
     * Store an external file (e.g. a downloaded WordPress attachment) through
     * the same optimization pipeline, so all media follows one convention.
     */
    public function importFromContents(string $contents, string $originalName, ?int $userId = null): Media
    {
        $tmp = tempnam(sys_get_temp_dir(), 'media-import-');
        file_put_contents($tmp, $contents);

        try {
            $file = new UploadedFile($tmp, $originalName, mime_content_type($tmp) ?: null, test: true);

            return $this->upload($file, null, $userId);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Read width/height for an existing Media row (backfill / repair).
     * Updates the model in place; returns true when something changed.
     */
    public function backfillDimensions(Media $media): bool
    {
        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return false;
        }

        $disk = Storage::disk($media->disk);
        $changed = false;

        $fullPath = $disk->path($media->path);
        if (is_file($fullPath)) {
            [$w, $h] = $this->readDimensions($fullPath);
            if ($w && $h && ($media->width !== $w || $media->height !== $h)) {
                $media->width = $w;
                $media->height = $h;
                $changed = true;
            }
        }

        $variants = $media->variants ?? [];
        $newVariants = [];
        foreach ($variants as $name => $entry) {
            $path = Media::variantPath($entry);
            if ($path === null) {
                $newVariants[$name] = $entry;

                continue;
            }

            $meta = is_array($entry) ? $entry : ['path' => $path];
            $variantFull = $disk->path($path);
            if (is_file($variantFull) && (empty($meta['width']) || empty($meta['height']))) {
                [$vw, $vh] = $this->readDimensions($variantFull);
                if ($vw && $vh) {
                    $meta['width'] = $vw;
                    $meta['height'] = $vh;
                    $changed = true;
                }
            }
            $meta['path'] = $path;
            $newVariants[$name] = $meta;
        }

        if ($newVariants !== $variants) {
            $media->variants = $newVariants === [] ? null : $newVariants;
            $changed = true;
        }

        if ($changed) {
            $media->save();
        }

        return $changed;
    }

    /**
     * @return array{0: string, 1: int, 2: array<string, array{path: string, width: int, height: int}>, 3: int, 4: int}
     */
    private function storeOptimizedImage(UploadedFile $file, string $dir): array
    {
        $manager = new ImageManager(new Driver);
        $disk = Storage::disk('public');
        $base = Str::random(20);

        // Re-encoding drops EXIF/GPS metadata as a side effect.
        $image = $manager->decodePath($file->getRealPath());
        $image->scaleDown(width: self::MAX_ORIGINAL_WIDTH);

        $path = "{$dir}/{$base}.webp";
        $disk->put($path, (string) $image->encode(new WebpEncoder(quality: 82)));

        $origWidth = $image->width();
        $origHeight = $image->height();

        $variants = [];
        foreach (self::VARIANTS as $name => $width) {
            if ($origWidth <= $width) {
                continue; // don't upscale; AppImage falls back to the original
            }

            $variant = $manager->decodePath($file->getRealPath());
            $variant->scaleDown(width: $width);

            $variantPath = "{$dir}/{$base}-{$name}.webp";
            $disk->put($variantPath, (string) $variant->encode(new WebpEncoder(quality: 80)));
            $variants[$name] = [
                'path' => $variantPath,
                'width' => $variant->width(),
                'height' => $variant->height(),
            ];
        }

        return [$path, $disk->size($path), $variants, $origWidth, $origHeight];
    }

    /** @return array{0: int|null, 1: int|null} */
    private function readDimensions(string $absolutePath): array
    {
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->decodePath($absolutePath);

            return [$image->width(), $image->height()];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
