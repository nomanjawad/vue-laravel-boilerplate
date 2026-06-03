<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $tablesExist = Schema::hasTable('menus') && Schema::hasTable('site_settings');

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],

            'menus' => $tablesExist ? [
                'header' => Menu::where('location', 'header')
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get(['id', 'title', 'url', 'sort_order']),
                'footer' => Menu::where('location', 'footer')
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get(['id', 'title', 'url', 'sort_order']),
            ] : ['header' => [], 'footer' => []],

            'settings' => $tablesExist
                ? Setting::pluck('value', 'key')->toArray()
                : [],

            'enabledFeatures' => config('template.features'),

            'cartCount' => fn () => count($request->session()->get('cart', [])),
        ];
    }
}
