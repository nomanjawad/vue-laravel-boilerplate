<?php

namespace App\Providers;

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
    }
}
