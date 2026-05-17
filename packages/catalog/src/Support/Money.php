<?php

declare(strict_types=1);

namespace Acme\Catalog\Support;

/**
 * Plain price formatter. Stays display-only on purpose — once you need
 * arithmetic across currencies, swap in a money library at the host level
 * and rebind any consumer that calls Money::format directly.
 */
final class Money
{
    public static function format(int $cents, string $currency = 'USD'): string
    {
        $minor = (int) config('acme.catalog.currency.minor_unit', 2);
        $symbol = (string) config('acme.catalog.currency.symbol', '$');
        $pos    = (string) config('acme.catalog.currency.symbol_position', 'left');

        $amount = number_format($cents / (10 ** $minor), $minor);
        $body   = $pos === 'right' ? "{$amount} {$symbol}" : "{$symbol}{$amount}";

        return $currency === (string) config('acme.catalog.currency.default')
            ? $body
            : "{$body} {$currency}";
    }
}
