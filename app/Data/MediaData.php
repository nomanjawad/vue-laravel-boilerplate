<?php

namespace App\Data;

use App\Models\Media;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaData extends Data
{
    /**
     * @param  array<string, string>|null  $variants
     */
    public function __construct(
        public int $id,
        public string $url,
        public ?array $variants,
        public ?string $alt_text,
        public string $filename,
        public string $mime_type,
        public int $size,
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
        );
    }
}
