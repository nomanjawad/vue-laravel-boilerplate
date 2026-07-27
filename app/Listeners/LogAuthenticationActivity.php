<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

/**
 * Writes admin authentication events to the spatie/laravel-activitylog stream.
 *
 * Closes the "who logged in / who failed / who logged out" half of the audit
 * trail — the other half (CRUD writes) is covered by App\Models\Concerns\
 * LogsContentActivity on every content model.
 *
 * Registered in App\Providers\AppServiceProvider::boot() so the events fire
 * for every auth guard the app uses.
 */
class LogAuthenticationActivity
{
    public function login(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'guard' => $event->guard,
            ])
            ->log('login');
    }

    public function logout(Logout $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => Request::ip(),
                'guard' => $event->guard,
            ])
            ->log('logout');
    }

    public function failed(Failed $event): void
    {
        // Never log the submitted password — Failed::credentials contains it.
        $credentials = $event->credentials;
        unset($credentials['password'], $credentials['password_confirmation']);

        activity('auth')
            ->withProperties([
                'ip' => Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'guard' => $event->guard,
                'credentials' => $credentials,
            ])
            ->log('login_failed');
    }
}
