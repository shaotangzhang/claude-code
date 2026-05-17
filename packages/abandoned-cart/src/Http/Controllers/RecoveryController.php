<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Http\Controllers;

use Acme\AbandonedCart\Services\AbandonmentService;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class RecoveryController extends Controller
{
    public function show(
        AbandonmentService $svc,
        UserResolver $users,
        string $token,
    ): RedirectResponse {
        try {
            $svc->recover($token, $users->currentUserId());
        } catch (\Throwable $e) {
            return redirect(url(config('acme.cart.route_prefix', 'cart')))
                ->withErrors(['recovery' => $e->getMessage()]);
        }

        return redirect(url(config('acme.cart.route_prefix', 'cart')))
            ->with('status', 'Welcome back — your cart is ready.');
    }
}
