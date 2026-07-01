<?php

namespace App\Data;

use App\Models\Tag;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TagSummaryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Tag $tag): self
    {
        return new self(
            id: $tag->id,
            name: $tag->name,
            slug: $tag->slug,
        );
    }
}
