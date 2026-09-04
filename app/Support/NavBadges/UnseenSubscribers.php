<?php

namespace App\Support\NavBadges;

use App\Models\Subscriber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cheap unread count for the Subscribers sidebar badge.
 * Cached 60s; stamped seen_at when the index is viewed.
 */
class UnseenSubscribers
{
    public function __invoke(): int
    {
        if (! Schema::hasTable('subscribers') || ! Schema::hasColumn('subscribers', 'seen_at')) {
            return 0;
        }

        return (int) Cache::remember('nav.badge.unseen_subscribers', 60, function () {
            return Subscriber::query()->whereNull('seen_at')->count();
        });
    }
}
