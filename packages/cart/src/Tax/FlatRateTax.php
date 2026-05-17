<?php

declare(strict_types=1);

namespace Acme\Cart\Tax;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\TaxCalculator;

/**
 * Single-rate tax expressed in basis points (1% = 100 bps).
 * Default implementation — bind a real per-jurisdiction calculator to
 * Acme\Contracts\Commerce\TaxCalculator in your host project to override.
 */
final class FlatRateTax implements TaxCalculator
{
    public function calculate(int $taxableSubtotalCents, string $currency, ?Address $destination): int
    {
        $bps = (int) config('acme.cart.tax.flat_rate_bps', 0);
        if ($bps <= 0 || $taxableSubtotalCents <= 0) {
            return 0;
        }

        return intdiv($taxableSubtotalCents * $bps, 10_000);
    }

    public function label(string $currency, ?Address $destination): string
    {
        return (string) config('acme.cart.tax.label', 'Tax');
    }
}
