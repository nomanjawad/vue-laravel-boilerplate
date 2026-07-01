<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Authenticated user shape shared with every Inertia page.
 */
#[TypeScript]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        /** @var array<int, string> */
        public array $roles,
        /** @var array<int, string> */
        public array $permissions,
        public bool $is_super_admin,
    ) {}

    public static function fromModel(User $user, ?array $permissions = null, ?bool $isSuperAdmin = null): self
    {
        // Prefer the already-eager-loaded roles relation (see
        // HandleInertiaRequests::share) so this DTO is a no-query
        // materialization when built on the shared-prop path.
        $roles = $user->relationLoaded('roles')
            ? $user->roles->pluck('name')->all()
            : $user->getRoleNames()->all();

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            roles: $roles,
            permissions: $permissions ?? $user->getAllPermissions()->pluck('name')->toArray(),
            is_super_admin: $isSuperAdmin ?? in_array('super-admin', $roles, true),
        );
    }
}
