<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]

/**
 * Authenticated user shape shared with every Inertia page.
 */
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

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            roles: $user->getRoleNames()->toArray(),
            permissions: $user->getAllPermissions()->pluck('name')->toArray(),
            is_super_admin: $user->hasRole('super-admin'),
        );
    }
}
