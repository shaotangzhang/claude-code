<?php

declare(strict_types=1);

namespace Acme\Membership\Http\Controllers;

use Acme\Contracts\Auth\UserResolver;
use Acme\Membership\Models\Plan;
use Acme\Membership\Models\Subscription;
use Acme\Membership\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class SubscriptionController extends Controller
{
    public function subscribe(
        Request $request,
        SubscriptionService $svc,
        UserResolver $users,
    ): RedirectResponse {
        $userId = $users->currentUserId();
        if (! $userId) {
            return redirect()->route('acme.auth.login');
        }

        $request->validate(['plan_id' => 'required|string']);
        $plan = Plan::query()->where('active', true)->findOrFail((string) $request->input('plan_id'));

        $svc->start($userId, $plan);

        return redirect()->route('acme.membership.show')->with('status', 'Subscription created.');
    }

    public function cancel(
        Request $request,
        SubscriptionService $svc,
        UserResolver $users,
        Subscription $subscription,
    ): RedirectResponse {
        abort_unless($subscription->user_id === $users->currentUserId(), 403);

        $svc->cancel($subscription, atPeriodEnd: ! $request->boolean('immediate'));

        return back()->with('status', 'Subscription canceled.');
    }

    public function pause(
        Request $request,
        SubscriptionService $svc,
        UserResolver $users,
        Subscription $subscription,
    ): RedirectResponse {
        abort_unless($subscription->user_id === $users->currentUserId(), 403);

        $until = $request->filled('until') ? \Carbon\CarbonImmutable::parse($request->string('until')->toString()) : null;
        $svc->pause($subscription, $until);

        return back()->with('status', 'Subscription paused.');
    }

    public function resume(
        SubscriptionService $svc,
        UserResolver $users,
        Subscription $subscription,
    ): RedirectResponse {
        abort_unless($subscription->user_id === $users->currentUserId(), 403);

        $svc->resume($subscription);

        return back()->with('status', 'Subscription resumed.');
    }
}
