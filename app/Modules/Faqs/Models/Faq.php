<?php

namespace App\Modules\Faqs\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;
    use ClearsResponseCache;
    use LogsContentActivity;

    protected $fillable = [
        'title',
        'page_slug',
        'body',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'published_at' => 'datetime',
    ];

    /**
     * FAQs assigned to a specific page slug (data/pages/{slug}.json).
     * Named forPageSlug (not forPage) to avoid clashing with Eloquent's
     * Builder::forPage() used by paginate().
     */
    public function scopeForPageSlug(Builder $query, string $slug): Builder
    {
        return $query->where('page_slug', $slug);
    }

    /**
     * Global / unassigned FAQs (page_slug IS NULL).
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('page_slug');
    }

    // v2 convention: routes declare their own keys ({model:slug} public,
    // default id-binding admin). Do NOT add getRouteKeyName() here.

    protected static function newFactory()
    {
        return \App\Modules\Faqs\Database\Factories\FaqFactory::new();
    }
}
