<?php

declare(strict_types=1);

namespace Acme\Auth\Support;

use Acme\Contracts\Auth\UserResolver;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Access\Gate;

final class AuthUserResolver implements UserResolver
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Gate $gate,
    ) {}

    public function currentUserId(): ?string
    {
        $user = $this->auth->guard()->user();

        return $user?->getAuthIdentifier() === null ? null : (string) $user->getAuthIdentifier();
    }

    public function currentUserCan(string $capability): bool
    {
        return $this->gate->allows($capability);
    }
}
