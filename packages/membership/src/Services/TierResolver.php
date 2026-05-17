<?php

declare(strict_types=1);

namespace Acme\Membership\Services;

use Acme\Membership\Models\Subscription;
use Acme\Membership\Models\Tier;

/**
 * Resolves the effective tier for a user — the highest-level tier among
 * their tier-granting subscriptions. Null if none.
 */
final class TierResolver
{
    public function forUser(string $userId): ?Tier
    {
        $sub = Subscription::query()
            ->forUser($userId)
            ->grantingTier()
            ->with('plan.tier')
            ->get()
            ->sortByDesc(fn ($s) => $s->plan?->tier?->level ?? -1)
            ->first();

        return $sub?->plan?->tier;
    }

    public function userHasAtLeast(string $userId, int $level): bool
    {
        $tier = $this->forUser($userId);

        return $tier !== null && $tier->level >= $level;
    }
}
