<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * A line-level adjustment computed by a provider against a cart.
 * Negative amount = discount; positive = surcharge.
 *
 * sourceKey is the stable identifier the consumer can show / log
 * ("campaign:summer-sale", "loyalty:redeem:200pts", ...).
 */
final readonly class CartAdjustment
{
    public const TARGET_DISCOUNT = 'discount';
    public const TARGET_SHIPPING = 'shipping';
    public const TARGET_TAX      = 'tax';

    public function __construct(
        public string $sourceKey,
        public string $description,
        public int $amountCents,
        public string $target = self::TARGET_DISCOUNT,
    ) {}
}
