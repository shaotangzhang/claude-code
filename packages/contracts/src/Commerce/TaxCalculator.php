<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

interface TaxCalculator
{
    /**
     * Return tax amount in cents for the given taxable subtotal under the
     * supplied destination. Implementations must be pure functions of the
     * arguments — no DB writes, no side effects.
     */
    public function calculate(int $taxableSubtotalCents, string $currency, ?Address $destination): int;

    /** Human-friendly label for the applied rate, e.g. "VAT 20%". */
    public function label(string $currency, ?Address $destination): string;
}
