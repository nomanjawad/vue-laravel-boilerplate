<?php

namespace App\Services;

use App\Data\SearchGroupData;
use App\Data\SearchResultData;
use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Modules\Core\ModuleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Scans each enabled module's `searchable` manifest entries. Top 5 hits per
 * group; permission-gated per resource. No Scout — plain LIKE queries.
 */
class AdminSearchService
{
    private const LIMIT_PER_GROUP = 5;

    /** @var array<class-string<Model>, array{label:string, permission:string, route:string, title:string}> */
    private const MODEL_META = [
        Post::class => [
            'label' => 'Posts',
            'permission' => 'posts.view',
            'route' => 'admin.posts.edit',
            'title' => 'title',
        ],
        Category::class => [
            'label' => 'Categories',
            'permission' => 'categories.view',
            'route' => 'admin.categories.edit',
            'title' => 'name',
        ],
        Tag::class => [
            'label' => 'Tags',
            'permission' => 'tags.view',
            'route' => 'admin.tags.index',
            'title' => 'name',
        ],
        Product::class => [
            'label' => 'Products',
            'permission' => 'products.view',
            'route' => 'admin.products.edit',
            'title' => 'name',
        ],
        Order::class => [
            'label' => 'Orders',
            'permission' => 'orders.view',
            'route' => 'admin.orders.show',
            'title' => 'order_number',
        ],
        Career::class => [
            'label' => 'Careers',
            'permission' => 'careers.view',
            'route' => 'admin.careers.edit',
            'title' => 'title',
        ],
        CaseStudy::class => [
            'label' => 'Case Studies',
            'permission' => 'case_studies.view',
            'route' => 'admin.case-studies.edit',
            'title' => 'title',
        ],
        Team::class => [
            'label' => 'Team',
            'permission' => 'teams.view',
            'route' => 'admin.teams.edit',
            'title' => 'name',
        ],
    ];

    public function __construct(private ModuleManager $modules) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, User $user): array
    {
        $term = trim($query);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $isSuperAdmin = $user->hasRole('super-admin');
        $like = '%'.addcslashes($term, '%_\\').'%';

        $groups = [];

        foreach ($this->modules->manifests() as $moduleKey => $manifest) {
            if (! $this->modules->enabled($moduleKey)) {
                continue;
            }

            foreach ($manifest['searchable'] ?? [] as $modelClass => $columns) {
                if (! is_string($modelClass) || ! class_exists($modelClass)) {
                    continue;
                }

                if (! is_subclass_of($modelClass, Model::class)) {
                    continue;
                }

                $meta = self::MODEL_META[$modelClass] ?? null;
                if (! $meta) {
                    continue;
                }

                if (! $isSuperAdmin && ! $user->can($meta['permission'])) {
                    continue;
                }

                if (! is_array($columns) || $columns === []) {
                    continue;
                }

                $validColumns = array_values(array_filter(
                    $columns,
                    fn ($c) => is_string($c) && preg_match('/^[a-z_][a-z0-9_]*$/i', $c),
                ));

                if ($validColumns === []) {
                    // No safe columns → do not fall back to a bare query
                    // (would return every row up to LIMIT_PER_GROUP as a
                    // "match"). Skip this model entirely.
                    continue;
                }

                /** @var Builder<Model> $builder */
                $builder = $modelClass::query()
                    ->where(function (Builder $q) use ($validColumns, $like) {
                        foreach ($validColumns as $column) {
                            $q->orWhere($column, 'like', $like);
                        }
                    })
                    ->limit(self::LIMIT_PER_GROUP);

                $results = [];
                foreach ($builder->get() as $record) {
                    $results[] = SearchResultData::from([
                        'id' => $record->getKey(),
                        'title' => $this->titleFor($record, $meta['title']),
                        'subtitle' => $this->subtitleFor($record, $modelClass),
                        'href' => $this->hrefFor($record, $meta['route']),
                    ]);
                }

                if ($results === []) {
                    continue;
                }

                $groups[] = SearchGroupData::from([
                    'module' => $moduleKey,
                    'label' => $meta['label'],
                    'results' => $results,
                ])->toArray();
            }
        }

        return $groups;
    }

    private function titleFor(Model $record, string $title): string
    {
        $value = $record->getAttribute($title);

        return $value !== null && $value !== ''
            ? (string) $value
            : '#'.$record->getKey();
    }

    private function subtitleFor(Model $record, string $modelClass): ?string
    {
        return match ($modelClass) {
            Post::class => $record->getAttribute('status') ? Str::title((string) $record->getAttribute('status')) : null,
            Product::class => $record->getAttribute('price') !== null ? (string) $record->getAttribute('price') : null,
            Order::class => $record->getAttribute('customer_email') ? (string) $record->getAttribute('customer_email') : null,
            Team::class => $record->getAttribute('position') ? (string) $record->getAttribute('position') : null,
            Category::class, Tag::class => null,
            default => null,
        };
    }

    private function hrefFor(Model $record, string $routeName): string
    {
        return route($routeName, $record->getKey());
    }
}
