<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    private const IMAGE_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
    ];

    /** Max bytes for paste/import fetches (matches upload max). */
    private const MAX_IMPORT_BYTES = 10 * 1024 * 1024;

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
    public function importFromContents(string $contents, string $originalName, ?int $userId = null, ?string $altText = null): Media
    {
        if (strlen($contents) > self::MAX_IMPORT_BYTES) {
            throw new InvalidArgumentException('Imported file exceeds the 10 MB limit.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'media-import-');
        file_put_contents($tmp, $contents);

        try {
            $detected = mime_content_type($tmp) ?: null;
            $file = new UploadedFile($tmp, $originalName, $detected, test: true);

            return $this->upload($file, $altText, $userId);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Fetch a remote image (paste from Google Docs / Word) into the library.
     * SSRF-hardened: http(s) only, no private/link-local hosts, images only.
     */
    public function importFromUrl(string $url, ?string $originalName = null, ?int $userId = null, ?string $altText = null): Media
    {
        $url = trim($url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid image URL.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http(s) image URLs are allowed.');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || $this->isBlockedHost($host)) {
            throw new InvalidArgumentException('That image host is not allowed.');
        }

        $response = Http::timeout(15)
            ->withHeaders(['Accept' => 'image/*,*/*;q=0.8'])
            ->withOptions(['allow_redirects' => ['max' => 3]])
            ->get($url);

        if (! $response->successful()) {
            throw new InvalidArgumentException('Failed to download image (HTTP '.$response->status().').');
        }

        $contents = $response->body();
        if ($contents === '' || strlen($contents) > self::MAX_IMPORT_BYTES) {
            throw new InvalidArgumentException('Imported image is empty or exceeds the 10 MB limit.');
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if ($contentType !== '' && ! in_array($contentType, self::IMAGE_MIMES, true)) {
            // Some CDNs send application/octet-stream; sniff from bytes below.
            if ($contentType !== 'application/octet-stream') {
                throw new InvalidArgumentException("Refusing to import non-image Content-Type [{$contentType}].");
            }
        }

        $name = $originalName ?: basename((string) ($parts['path'] ?? 'pasted-image'));
        if ($name === '' || $name === '/' || ! str_contains($name, '.')) {
            $ext = match ($contentType) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };
            $name = 'pasted-image.'.$ext;
        }

        return $this->importFromContents($contents, $name, $userId, $altText);
    }

    /**
     * Decode a data:image/…;base64,… payload into the media library.
     */
    public function importFromDataUrl(string $dataUrl, ?string $originalName = null, ?int $userId = null, ?string $altText = null): Media
    {
        if (! preg_match('#^data:(image/(?:jpeg|jpg|png|webp|gif));base64,(.+)$#i', $dataUrl, $m)) {
            throw new InvalidArgumentException('Invalid or unsupported data URL (images only).');
        }

        $mime = strtolower($m[1]);
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }

        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Could not decode data URL.');
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        return $this->importFromContents(
            $binary,
            $originalName ?: 'pasted-image.'.$ext,
            $userId,
            $altText,
        );
    }

    private function isBlockedHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return true;
        }

        // IPv6 loopback / link-local shorthand
        if ($host === '::1' || str_starts_with($host, 'fe80:') || str_starts_with($host, '[')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPrivateIp($host);
        }

        // Resolve once and reject private answers (basic SSRF guard).
        $ips = @gethostbynamel($host) ?: [];
        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ! filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ! filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        return true;
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
