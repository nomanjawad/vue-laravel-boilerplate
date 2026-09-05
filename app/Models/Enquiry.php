<?php

namespace App\Models;

use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use LogsContentActivity;

    /** @var string */
    protected string $activityLogName = 'default';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'ip',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
