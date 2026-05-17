<?php

declare(strict_types=1);

namespace Acme\Membership\Listeners;

use Acme\Membership\Events\SubscriptionExpired;
use Acme\Membership\Events\SubscriptionStarted;
use Acme\Rbac\Models\Role;

/**
 * Optional rbac coupling. If config('acme.membership.tier_to_role') maps
 * a tier key to a role key, we grant/revoke that role on
 * SubscriptionStarted / SubscriptionExpired.
 *
 * Hosts can disable by leaving the config map empty.
 */
final class SyncTierRole
{
    public function onStarted(SubscriptionStarted $event): void
    {
        $role = $this->roleForTier($event->tierKey);
        if (! $role) { return; }

        \Illuminate\Support\Facades\DB::table('acme_rbac_role_user')->insertOrIgnore([
            'role_id' => $role->id,
            'user_id' => $event->userId,
        ]);
    }

    public function onExpired(SubscriptionExpired $event): void
    {
        $role = $this->roleForTier($event->tierKey);
        if (! $role) { return; }

        \Illuminate\Support\Facades\DB::table('acme_rbac_role_user')
            ->where('role_id', $role->id)->where('user_id', $event->userId)->delete();
    }

    private function roleForTier(string $tierKey): ?Role
    {
        $map = (array) config('acme.membership.tier_to_role', []);
        $roleKey = $map[$tierKey] ?? null;
        if (! $roleKey) { return null; }

        return Role::query()->where('key', $roleKey)->first();
    }
}
