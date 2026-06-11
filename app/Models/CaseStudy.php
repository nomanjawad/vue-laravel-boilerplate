<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    use ClearsResponseCache;
    use HasFactory;
    use LogsContentActivity;

    protected $fillable = [
        'title', 'slug', 'client_name', 'excerpt', 'body',
        'featured_image', 'results', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'results' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
