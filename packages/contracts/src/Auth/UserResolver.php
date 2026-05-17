<?php

declare(strict_types=1);

namespace Acme\Contracts\Auth;

interface UserResolver
{
    /** Return the currently authenticated user's stable identifier, or null. */
    public function currentUserId(): ?string;

    /** Does the current user hold the given capability? */
    public function currentUserCan(string $capability): bool;
}
