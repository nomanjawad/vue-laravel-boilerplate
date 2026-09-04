<?php

namespace App\Data;

use App\Models\Category;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategorySummaryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug = null,
        public ?int $parent_id = null,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            parent_id: $category->parent_id,
        );
    }
}
