<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationActivity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super-admin bypass: a user with the `super-admin` role passes every
        // gate / policy / can: check without explicitly assigning each
        // permission. Standard `admin` role still gets the granular permission
        // matrix synced from the module manifests via PermissionSyncer.
        Gate::before(function ($user, $ability) {
            return $user?->hasRole('super-admin') ? true : null;
        });

        // Auth event → activity-log stream. Content CRUD is covered by the
        // LogsContentActivity trait on every content model; this closes the
        // "who logged in" half so /admin/audit-log tells the full story.
        Event::listen(Login::class,  [LogAuthenticationActivity::class, 'login']);
        Event::listen(Logout::class, [LogAuthenticationActivity::class, 'logout']);
        Event::listen(Failed::class, [LogAuthenticationActivity::class, 'failed']);
    }
}
