<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function upload(UploadedFile $file, ?string $altText = null, ?int $userId = null): Media
    {
        $mime = $file->getMimeType();
        $dir = 'media/'.date('Y/m');

        if (in_array($mime, self::OPTIMIZABLE, true)) {
            [$path, $size, $variants] = $this->storeOptimizedImage($file, $dir);
            $mime = 'image/webp';
        } else {
            // Non-optimizable uploads (SVG, GIF, PDF, ...) are stored as-is.
            $path = $file->store($dir, 'public');
            $size = $file->getSize();
            $variants = null;
        }

        return Media::create([
            'user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'alt_text' => $altText,
            'variants' => $variants,
        ]);
    }

    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $disk->delete($media->path);

        foreach ($media->variants ?? [] as $variantPath) {
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

    /** @return array{0: string, 1: int, 2: array<string, string>} [path, size, variants] */
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

        $variants = [];
        foreach (self::VARIANTS as $name => $width) {
            if ($image->width() <= $width) {
                continue; // don't upscale; imageUrl falls back to the original
            }

            $variant = $manager->decodePath($file->getRealPath());
            $variant->scaleDown(width: $width);

            $variantPath = "{$dir}/{$base}-{$name}.webp";
            $disk->put($variantPath, (string) $variant->encode(new WebpEncoder(quality: 80)));
            $variants[$name] = $variantPath;
        }

        return [$path, $disk->size($path), $variants];
    }
}
