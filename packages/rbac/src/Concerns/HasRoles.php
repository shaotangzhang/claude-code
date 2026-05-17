<?php

declare(strict_types=1);

namespace Acme\Rbac\Concerns;

use Acme\Rbac\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Apply on the host's concrete User model:
 *   class User extends \Acme\Auth\Models\User {
 *       use \Acme\Rbac\Concerns\HasRoles;
 *   }
 *
 * The base acme/auth User has no knowledge of rbac (correct: layer 1 ↔ layer 1
 * communication via opt-in trait, not inheritance coupling).
 */
trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'acme_rbac_role_user', 'user_id', 'role_id');
    }

    public function hasCapability(string $key): bool
    {
        return $this->roles()
            ->whereHas('capabilities', fn ($q) => $q->where('key', $key))
            ->exists();
    }
}
