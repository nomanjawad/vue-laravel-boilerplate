<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AuthData extends Data
{
    public function __construct(
        public ?UserData $user,
    ) {}

    public static function fromUser(?User $user, ?array $permissions = null, ?bool $isSuperAdmin = null): self
    {
        return new self(
            user: $user ? UserData::fromModel($user, $permissions, $isSuperAdmin) : null,
        );
    }
}
