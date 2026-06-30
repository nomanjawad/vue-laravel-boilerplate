<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'key',
        'type',
        'enabled',
        'installed_version',
        'unhealthy',
        'last_error',
        'last_error_at',
        'settings',
        'installed_at',
        'disabled_at',
    ];

    protected $casts = [
        'enabled' => 'bool',
        'unhealthy' => 'bool',
        'settings' => 'array',
        'last_error_at' => 'datetime',
        'installed_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
