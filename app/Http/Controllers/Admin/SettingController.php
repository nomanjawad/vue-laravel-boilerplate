<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function __construct(private SettingService $settingService) {}

    public function index()
    {
        // Redact secret values before shipping to Inertia. Without this, a
        // stored SMTP/gateway/webhook secret would be serialised into the
        // page payload and be visible in view-source and the Inertia JSON
        // response. See feedback.md §8.
        $settings = Setting::all()
            ->map(function (Setting $setting): Setting {
                if ($setting->is_secret) {
                    $setting->setAttribute('value', '');
                }

                return $setting;
            })
            ->groupBy('group');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        // Blank submission on a secret field means "keep the existing value" —
        // the field was redacted on GET, so the browser never had the real
        // secret to send back. Without this, saving the form would wipe any
        // secret whose input the admin didn't re-type.
        $secretKeys = Setting::query()->where('is_secret', true)->pluck('key')->all();
        foreach ($secretKeys as $key) {
            if (array_key_exists($key, $validated['settings']) && ($validated['settings'][$key] ?? '') === '') {
                unset($validated['settings'][$key]);
            }
        }

        $this->settingService->update($validated['settings']);

        return back()->with('success', 'Settings updated successfully.');
    }
}
