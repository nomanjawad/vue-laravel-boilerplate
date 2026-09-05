<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Audit trail for content models (spatie/laravel-activitylog): answers
 * "who changed/deleted this?" from the activity_log table.
 *
 * Optional model properties:
 *   - $activityLogName (string) — stream filter (media, users, …)
 *   - $activityLogExcept (list<string>) — attributes never logged (e.g. password)
 */
trait LogsContentActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();

        if (property_exists($this, 'activityLogName') && is_string($this->activityLogName) && $this->activityLogName !== '') {
            $options->useLogName($this->activityLogName);
        }

        if (property_exists($this, 'activityLogExcept') && is_array($this->activityLogExcept) && $this->activityLogExcept !== []) {
            $options->logExcept($this->activityLogExcept);
        }

        return $options;
    }
}
