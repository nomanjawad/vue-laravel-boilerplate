<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use ClearsResponseCache;
    use HasFactory;
    use LogsContentActivity;

    public const UNCATEGORIZED_SLUG = 'uncategorized';

    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public static function uncategorized(): self
    {
        return static::firstOrCreate(
            ['slug' => self::UNCATEGORIZED_SLUG],
            ['name' => 'Uncategorized', 'sort_order' => 0]
        );
    }

    public function isUncategorized(): bool
    {
        return $this->slug === self::UNCATEGORIZED_SLUG;
    }

    /**
     * IDs of all descendants (not including $this).
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];
        while ($frontier !== []) {
            $children = static::whereIn('parent_id', $frontier)->pluck('id')->all();
            if ($children === []) {
                break;
            }
            $ids = [...$ids, ...$children];
            $frontier = $children;
        }

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    public function selfAndDescendantIds(): array
    {
        return [$this->id, ...$this->descendantIds()];
    }
}
