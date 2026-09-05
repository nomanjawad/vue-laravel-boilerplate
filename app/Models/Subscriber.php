<?php

namespace App\Models;

use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use LogsContentActivity;

    /** @var string */
    protected string $activityLogName = 'default';

    protected $fillable = ['email', 'name', 'ip', 'unsubscribed_at', 'seen_at'];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
            'seen_at' => 'datetime',
        ];
    }
}
