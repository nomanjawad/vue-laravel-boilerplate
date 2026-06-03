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
        $validated = $request->validate([
            'location' => ['required', 'in:header,footer'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        Menu::create($validated);

        return back()->with('success', 'Menu item created successfully.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $menu->update($validated);

        return back()->with('success', 'Menu item updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return back()->with('success', 'Menu item deleted successfully.');
    }
}
