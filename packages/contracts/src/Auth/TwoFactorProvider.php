<?php

declare(strict_types=1);

namespace Acme\Contracts\Auth;

interface TwoFactorProvider
{
    public function generateSecret(): string;

    public function qrCodeUrl(string $accountName, string $secret): string;

    public function verify(string $secret, string $code): bool;
}
