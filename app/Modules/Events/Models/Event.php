<?php

namespace App\Modules\Events\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Event extends Model
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
        return \App\Modules\Events\Database\Factories\EventFactory::new();
    }
}
