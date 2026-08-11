<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use ClearsResponseCache;
    use LogsContentActivity;

    protected $fillable = ['location', 'title', 'url', 'parent_id', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Internal paths saved without a leading slash render fine as link text
     * but break Inertia's client-side routing/active-state matching. External
     * URLs and in-page anchors are left untouched. See feedback.md §41.
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')
            || preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            return $url;
        }

        return '/'.$url;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }
}
