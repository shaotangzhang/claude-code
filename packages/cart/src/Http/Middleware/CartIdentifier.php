<?php

declare(strict_types=1);

namespace Acme\Cart\Http\Middleware;

use Acme\Cart\Services\CartService;
use Acme\Contracts\Auth\UserResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Ensures every request that touches cart routes has a resolvable cart
 * id. The cart is bound to the container so controllers can inject it
 * as a route-parameter-free dependency.
 */
final class CartIdentifier
{
    public function __construct(
        private readonly CartService $cart,
        private readonly UserResolver $users,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $cookieName = (string) config('acme.cart.cookie.name', 'acme_cart');
        $token      = $request->cookie($cookieName);
        $userId     = $this->users->currentUserId();
        $currency   = (string) config('acme.catalog.currency.default', 'USD');

        if (! $userId && ! $token) {
            $token = (string) Str::ulid();
            Cookie::queue(
                $cookieName, $token,
                (int) config('acme.cart.cookie.days', 30) * 24 * 60,
                null, null,
                (bool) config('acme.cart.cookie.secure', true),
                (bool) config('acme.cart.cookie.http_only', true),
                false,
                (string) config('acme.cart.cookie.same_site', 'lax'),
            );
        }

        $cart = $this->cart->findOrCreate($userId, $token, $currency, app()->getLocale());
        app()->instance(\Acme\Cart\Models\Cart::class, $cart);

        return $next($request);
    }
}
