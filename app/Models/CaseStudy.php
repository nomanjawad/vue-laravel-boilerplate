<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    use HasFactory;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
