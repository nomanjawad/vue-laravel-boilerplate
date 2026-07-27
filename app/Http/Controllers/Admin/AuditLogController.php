<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin activity log — everything spatie/laravel-activitylog has captured.
 *
 * Sources:
 *   - App\Models\Concerns\LogsContentActivity on every content model
 *     (create/update/delete on Post, Product, Testimonial, …).
 *   - App\Listeners\LogAuthenticationActivity for login / logout / failed.
 *
 * Permission-gated by `audit_log.view` (declared in the core manifest so
 * only admins see it).
 */
class AuditLogController extends Controller
{
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

        return Inertia::render('Admin/AuditLog/Index', [
            'activities' => $query->paginate(50)->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
