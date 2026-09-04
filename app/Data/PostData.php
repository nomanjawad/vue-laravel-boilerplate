<?php

namespace App\Data;

use App\Models\Post;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostData extends Data
{
    /**
     * @param  array<int, CategorySummaryData>  $categories
     * @param  array<int, TagSummaryData>  $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $body,
        public string $status,
        public ?string $featured_image,
        public ?string $meta_title,
        public ?string $meta_description,
        public ?string $og_image,
        public ?string $canonical_url,
        public ?string $og_title,
        public ?string $og_description,
        public ?string $focus_keyword,
        public bool $noindex,
        public ?UserSummaryData $user = null,
        public array $categories = [],
        public array $tags = [],
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            title: $post->title,
            slug: $post->slug,
            excerpt: $post->excerpt,
            body: $post->body,
            status: $post->status,
            featured_image: $post->featured_image,
            meta_title: $post->meta_title,
            meta_description: $post->meta_description,
            og_image: $post->og_image,
            canonical_url: $post->canonical_url,
            og_title: $post->og_title,
            og_description: $post->og_description,
            focus_keyword: $post->focus_keyword,
            noindex: $post->noindex,
            user: $post->relationLoaded('user') && $post->user
                ? UserSummaryData::fromModel($post->user)
                : null,
            categories: $post->relationLoaded('categories')
                ? $post->categories->map(fn ($cat) => CategorySummaryData::fromModel($cat))->values()->all()
                : [],
            tags: $post->relationLoaded('tags')
                ? $post->tags->map(fn ($tag) => TagSummaryData::fromModel($tag))->values()->all()
                : [],
        );
    }
}
