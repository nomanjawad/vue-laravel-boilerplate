<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use ClearsResponseCache;
    use HasFactory;
    use LogsContentActivity;

    protected $fillable = [
        'title', 'slug', 'body', 'template', 'is_published',
        'sort_order', 'meta_title', 'meta_description', 'og_image',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
