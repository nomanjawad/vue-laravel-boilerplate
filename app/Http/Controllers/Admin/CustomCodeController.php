<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomCodeController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/CustomCode/Index', [
            // `code` deliberately excluded — the list only needs enough to
            // toggle/reorder/delete; Edit loads the full snippet.
            'codes' => CustomCode::orderBy('placement')->orderBy('sort_order')
                ->get(['id', 'name', 'placement', 'is_active', 'sort_order']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CustomCode/Create');
    }

    public function store(Request $request)
    {
        CustomCode::create($this->validated($request));

        return redirect()->route('admin.custom-code.index')->with('success', 'Snippet created successfully.');
    }

    public function edit(CustomCode $customCode)
    {
        return Inertia::render('Admin/CustomCode/Edit', [
            'code' => $customCode,
        ]);
    }

    public function update(Request $request, CustomCode $customCode)
    {
        $customCode->update($this->validated($request));

        return redirect()->route('admin.custom-code.index')->with('success', 'Snippet updated successfully.');
    }

    public function destroy(CustomCode $customCode)
    {
        $customCode->delete();

        return back()->with('success', 'Snippet deleted successfully.');
    }

    /** Quick on/off from the list page — doesn't require resending the code body. */
    public function toggle(CustomCode $customCode)
    {
        $customCode->update(['is_active' => ! $customCode->is_active]);

        return back();
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'placement' => ['required', 'in:head,body_start,body_end'],
            'code' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        // Same reasoning as PostController::store/update — a switch that's
        // off may not send the key at all. Default false (not true): if the
        // key is somehow missing, treat the snippet as inactive rather than
        // silently (re)activating site-wide injected code.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
