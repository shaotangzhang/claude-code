<?php

declare(strict_types=1);

namespace Acme\Contracts\Auth;

interface SsoProvider
{
    public function key(): string;

    public function redirectUrl(string $stateToken): string;

    /** @return array{external_id:string, email:?string, name:?string, raw:array<string,mixed>} */
    public function exchange(string $code): array;
}
