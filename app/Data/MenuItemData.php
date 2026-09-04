<?php

namespace App\Data;

use App\Models\Menu;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MenuItemData extends Data
{
    /**
     * @param  array<int, MenuItemData>  $children
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public int $sort_order,
        public array $children = [],
    ) {}

    /**
     * Build a public tree node from a Menu root (with eager-loaded children).
     * Depth is capped at 2 (root + one child level) — grandchildren are ignored.
     */
    public static function fromMenu(Menu $menu): self
    {
        return new self(
            id: $menu->id,
            title: $menu->title,
            url: $menu->url,
            sort_order: (int) $menu->sort_order,
            children: $menu->children
                ->map(fn (Menu $child) => new self(
                    id: $child->id,
                    title: $child->title,
                    url: $child->url,
                    sort_order: (int) $child->sort_order,
                    children: [],
                ))
                ->values()
                ->all(),
        );
    }
}
