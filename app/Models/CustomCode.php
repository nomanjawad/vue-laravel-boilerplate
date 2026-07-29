<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-authored HTML/JS/CSS snippets injected verbatim into
 * resources/views/app.blade.php at one of three fixed placements — see
 * App\Services\CustomCodeService. Affects every public page's rendered
 * HTML, so ClearsResponseCache is required here, not optional.
 */
class CustomCode extends Model
{
    use ClearsResponseCache;
    use LogsContentActivity;

    protected $fillable = ['name', 'placement', 'code', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlacement($query, string $placement)
    {
        return $query->where('placement', $placement);
    }
}
