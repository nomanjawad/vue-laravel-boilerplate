<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function __construct(private SettingService $settingService)
    {
    }

    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::all()->groupBy('group'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->settingService->update($validated['settings']);

        return back()->with('success', 'Settings updated successfully.');
    }
}
