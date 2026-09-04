<?php

namespace App\Data;

use App\Models\Media;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaData extends Data
{
    /**
     * Variants map: name → storage path string (legacy) or
     * `{path, width, height}` (Phase 10). Frontend uses
     * `variantPath()` / `useImageUrl` to resolve.
     *
     * @param  array<string, mixed>|null  $variants
     */
    public function __construct(
        public int $id,
        public string $url,
        public ?array $variants,
        public ?string $alt_text,
        public string $filename,
        public string $mime_type,
        public int $size,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    public static function fromModel(Media $media): self
    {
        return new self(
            id: $media->id,
            url: $media->url,
            variants: $media->variants,
            alt_text: $media->alt_text,
            filename: $media->filename,
            mime_type: $media->mime_type,
            size: $media->size,
            width: $media->width,
            height: $media->height,
        );
    }
}
