<?php

declare(strict_types=1);

namespace Acme\Membership\Http\Controllers;

use Acme\Contracts\Auth\UserResolver;
use Acme\Membership\Models\Subscription;
use Acme\Membership\Services\TierResolver;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class MembershipController extends Controller
{
    public function show(UserResolver $users, TierResolver $tiers): Response
    {
        $userId = $users->currentUserId();
        $subs   = $userId
            ? Subscription::with('plan.tier')->forUser($userId)->orderByDesc('created_at')->get()
            : collect();
        $tier = $userId ? $tiers->forUser($userId) : null;

        return new Response((string) view('acme-membership::account.show', [
            'subscriptions' => $subs,
            'currentTier'   => $tier,
        ])->render());
    }
}
