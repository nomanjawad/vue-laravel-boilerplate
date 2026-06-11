<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    protected $fillable = ['path', 'referrer', 'user_agent', 'hit_count', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /** Record a 404 hit, aggregating by path so the table stays small. */
    public static function record(string $path, ?string $referrer, ?string $userAgent): void
    {
        $log = self::firstOrNew(['path' => mb_substr($path, 0, 191)]);

        $log->hit_count = $log->exists ? $log->hit_count + 1 : 1;
        $log->referrer = $referrer ? mb_substr($referrer, 0, 512) : $log->referrer;
        $log->user_agent = $userAgent ? mb_substr($userAgent, 0, 512) : $log->user_agent;
        $log->last_seen_at = now();
        $log->save();
    }
}
