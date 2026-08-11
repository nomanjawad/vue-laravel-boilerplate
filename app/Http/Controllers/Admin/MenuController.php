<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Menus/Index', [
            'headerMenus' => Menu::where('location', 'header')->whereNull('parent_id')->orderBy('sort_order')->get(),
            'footerMenus' => Menu::where('location', 'footer')->whereNull('parent_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // sort_order is nullable: the admin form binds a number input via
        // Vue's `.number` modifier, which emits an empty string (not 0 or
        // null) once the field is cleared — a bare `integer` rule 422s that
        // silently, with no error shown. See feedback.md §34.
        $validated = $request->validate([
            'location' => ['required', 'in:header,footer'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['url'] = Menu::normalizeUrl($validated['url']);
        $validated['sort_order'] ??= 0;

        Menu::create($validated);

        return back()->with('success', 'Menu item created successfully.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['url'] = Menu::normalizeUrl($validated['url']);
        // A cleared order field means "leave it as-is", not "reset to null".
        if (! array_key_exists('sort_order', $validated) || $validated['sort_order'] === null) {
            unset($validated['sort_order']);
        }

        $menu->update($validated);

        return back()->with('success', 'Menu item updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return back()->with('success', 'Menu item deleted successfully.');
    }
}
