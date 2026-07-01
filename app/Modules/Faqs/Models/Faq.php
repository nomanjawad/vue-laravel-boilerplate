<?php

namespace App\Modules\Faqs\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Faq extends Model
{
    use HasFactory;
    use ClearsResponseCache;
    use LogsContentActivity;


    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
        'published_at' => 'datetime',
    ];

    // v2 convention: routes declare their own keys ({model:slug} public,
    // default id-binding admin). Do NOT add getRouteKeyName() here.

    protected static function newFactory()
    {
        return \App\Modules\Faqs\Database\Factories\FaqFactory::new();
    }
}
