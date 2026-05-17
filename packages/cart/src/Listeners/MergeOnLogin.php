<?php

declare(strict_types=1);

namespace Acme\Cart\Listeners;

use Acme\Cart\Services\CartMerger;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * On Login, look for a guest cart cookie and fold its contents into the
 * authenticated user's cart. Idempotent: if there is no guest token,
 * or the token doesn't match an active guest cart, this is a no-op.
 */
final class MergeOnLogin
{
    public function __construct(
        private readonly CartMerger $merger,
        private readonly Request $request,
    ) {}

    public function handle(Login $event): void
    {
        $userId = (string) $event->user->getAuthIdentifier();
        $cookie = (string) config('acme.cart.cookie.name', 'acme_cart');
        $token  = $this->request->cookie($cookie);
        if (! $token) {
            return;
        }
        $this->merger->merge($userId, (string) $token);
    }
}
