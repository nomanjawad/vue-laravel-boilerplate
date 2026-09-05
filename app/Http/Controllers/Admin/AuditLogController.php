<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin activity log — everything spatie/laravel-activitylog has captured.
 *
 * Sources:
 *   - LogsContentActivity on content models (posts, media, users, …)
 *   - Manual activity() calls for JSON pages/layout, modules, cache, bulk ops
 *   - LogAuthenticationActivity for login / logout / failed
 *
 * Permission-gated by `audit_log.view`.
 */
class AuditLogController extends Controller
{
    /** @var list<string> */
    public const STREAMS = [
        'default',
        'auth',
        'pages',
        'layout',
        'media',
        'users',
        'modules',
        'system',
    ];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'log_name' => ['nullable', 'string', 'max:64'],
            'causer_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Activity::query()
            ->with(['causer:id,name,email'])
            ->latest();

        if (! empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }
        if (! empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }
        if (! empty($filters['search'])) {
            $like = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                    ->orWhere('subject_type', 'like', $like);
            });
        }

        $paginator = $query->paginate(50)->withQueryString()->through(
            fn (Activity $activity) => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'summary' => $this->summarize($activity),
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer
                    ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ]
                    : null,
                'properties' => $activity->properties?->toArray(),
                'created_at' => $activity->created_at?->toIso8601String(),
            ]
        );

        return Inertia::render('Admin/AuditLog/Index', [
            'activities' => $paginator,
            'filters' => $filters,
            'streams' => self::STREAMS,
        ]);
    }

    private function summarize(Activity $activity): string
    {
        $who = $activity->causer?->name ?? 'Someone';
        $desc = (string) $activity->description;
        $props = $activity->properties?->toArray() ?? [];

        if (in_array($desc, ['login', 'logout', 'login_failed'], true)) {
            return match ($desc) {
                'login' => "{$who} logged in",
                'logout' => "{$who} logged out",
                'login_failed' => 'Failed login attempt'.(isset($props['credentials']['email']) ? ' for '.$props['credentials']['email'] : ''),
                default => "{$who} {$desc}",
            };
        }

        // Manual logs already use human sentences ("updated page …", "cleared …").
        if (! in_array($desc, ['created', 'updated', 'deleted'], true) && str_contains($desc, ' ')) {
            return "{$who} {$desc}";
        }

        $subject = class_basename((string) ($activity->subject_type ?? 'item')) ?: 'item';
        $label = $this->subjectLabel($props, $subject);
        $verb = match ($desc) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            default => $desc,
        };

        return "{$who} {$verb} {$label}";
    }

    /** @param  array<string, mixed>  $props */
    private function subjectLabel(array $props, string $subject): string
    {
        $attrs = is_array($props['attributes'] ?? null) ? $props['attributes'] : [];
        $old = is_array($props['old'] ?? null) ? $props['old'] : [];

        foreach (['title', 'name', 'filename', 'email', 'key', 'slug'] as $field) {
            $value = $attrs[$field] ?? $old[$field] ?? null;
            if (is_string($value) && $value !== '') {
                return Str::lower($subject).' "'.$value.'"';
            }
        }

        return Str::lower($subject);
    }
}
