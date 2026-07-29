<?php

namespace App\Data;

use App\Models\Post;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostData extends Data
{
    /**
     * @param  array<int, TagSummaryData>  $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $body,
        public ?int $category_id,
        public string $status,
        public ?string $featured_image,
        public ?string $meta_title,
        public ?string $meta_description,
        public bool $noindex,
        public ?CategorySummaryData $category = null,
        public ?UserSummaryData $user = null,
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
            category_id: $post->category_id,
            status: $post->status,
            featured_image: $post->featured_image,
            meta_title: $post->meta_title,
            meta_description: $post->meta_description,
            noindex: $post->noindex,
            category: $post->relationLoaded('category') && $post->category
                ? CategorySummaryData::fromModel($post->category)
                : null,
            user: $post->relationLoaded('user') && $post->user
                ? UserSummaryData::fromModel($post->user)
                : null,
            tags: $post->relationLoaded('tags')
                ? $post->tags->map(fn ($tag) => TagSummaryData::fromModel($tag))->values()->all()
                : [],
        );
    }
}
