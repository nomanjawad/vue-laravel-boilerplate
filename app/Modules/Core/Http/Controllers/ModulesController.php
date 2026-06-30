<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class ModulesController extends Controller
{
    public function __construct(protected ModuleManager $manager) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Modules/Index', [
            'modules' => $this->manager->summary(),
        ]);
    }

    public function enable(string $key): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->manager->enable($key);
        } catch (ModuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Module \"{$key}\" enabled.");
    }

    public function disable(string $key): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->manager->disable($key);
        } catch (ModuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Module \"{$key}\" disabled.");
    }

    public function reinstall(string $key): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->manager->reinstall($key);
        } catch (ModuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Module \"{$key}\" reinstalled.");
    }

    public function uninstall(Request $request, string $key): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['confirm' => 'required|in:UNINSTALL']);

        try {
            $this->manager->uninstall($key);
        } catch (ModuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Module \"{$key}\" uninstalled.");
    }

    public function clearHealth(string $key): \Illuminate\Http\RedirectResponse
    {
        $this->manager->clearHealth($key);
        Artisan::call('optimize:clear');

        return back()->with('success', "Module \"{$key}\" health flag cleared.");
    }
}
