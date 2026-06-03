<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Teams/Index', [
            'members' => Team::orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Teams/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        Team::create($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Team member added.');
    }

    public function edit(Team $team)
    {
        return Inertia::render('Admin/Teams/Edit', [
            'member' => $team,
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $team->update($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Team member updated.');
    }

    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('admin.teams.index')->with('success', 'Team member removed.');
    }
}
