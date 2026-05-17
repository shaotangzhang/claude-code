<?php

declare(strict_types=1);

namespace Acme\Rbac\Gates;

use Acme\Auth\Models\User;
use Acme\Contracts\Module\CapabilityRegistry;
use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Binds one Gate per registered capability key. Each Gate checks whether the
 * acting user has a role granting that capability.
 *
 * The super role (config acme.rbac.super_role) short-circuits when
 * super_bypasses_all is true.
 */
final class CapabilityGate
{
    public function __construct(
        private readonly CapabilityRegistry $registry,
        private readonly Gate $gate,
    ) {}

    public function install(): void
    {
        foreach ($this->registry->all() as $cap) {
            $key = $cap['key'];
            $this->gate->define($key, fn ($user) => $user instanceof User && static::userHas($user, $key));
        }

        if (config('acme.rbac.super_bypasses_all', true)) {
            $superKey = (string) config('acme.rbac.super_role', 'super-admin');
            $this->gate->before(function ($user) use ($superKey) {
                if ($user instanceof User && $user->relationLoaded('roles')
                    ? $user->roles->contains('key', $superKey)
                    : $user instanceof User && $user->roles()->where('key', $superKey)->exists()
                ) {
                    return true;
                }

                return null;
            });
        }
    }

    private static function userHas(User $user, string $capability): bool
    {
        return $user->roles()
            ->whereHas('capabilities', fn ($q) => $q->where('key', $capability))
            ->exists();
    }
}
