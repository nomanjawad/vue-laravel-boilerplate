<?php

namespace App\Support\NavBadges;

use App\Models\Enquiry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cheap unread count for the Enquiries sidebar badge.
 * Cached 60s; busted when an enquiry is marked read/unread.
 */
class UnreadEnquiries
{
    public const CACHE_KEY = 'nav.badge.unread_enquiries';

    public function __invoke(): int
    {
        if (! Schema::hasTable('enquiries')) {
            return 0;
        }

        return (int) Cache::remember(self::CACHE_KEY, 60, function () {
            return Enquiry::query()->whereNull('read_at')->count();
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
