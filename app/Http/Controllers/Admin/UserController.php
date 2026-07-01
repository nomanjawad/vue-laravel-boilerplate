<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('roles')
                ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'string', Rule::in($this->assignableRoles()->pluck('name')->all())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        // A non-super-admin cannot open a super-admin's edit page — even
        // read access would leak the account exists and let them submit
        // update() with a crafted payload.
        if ($user->hasRole('super-admin') && ! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('super-admin') && ! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', Rules\Password::defaults()],
            'role' => ['required', 'string', Rule::in($this->assignableRoles()->pluck('name')->all())],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('super-admin') && ! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Roles the acting user is allowed to assign. Non-super-admins cannot
     * grant super-admin — that would let any user with `users.create` or
     * `users.update` escalate themselves.
     */
    private function assignableRoles(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Role::query()->orderBy('name');
        if (! auth()->user()?->hasRole('super-admin')) {
            $query->where('name', '!=', 'super-admin');
        }

        return $query->get();
    }
}
