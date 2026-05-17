<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * A "gift line" a provider wants inserted into a cart. The cart will
 * either insert a new gift line or update the existing one keyed on
 * sourceKey.
 *
 * Pricing: the line is created at the SKU's resolved unit price; the
 * cart pipeline auto-emits a 100%-off discount for every gift line so
 * the financial net effect is zero. Visibility: users see the line
 * with the description and a "Free" label, but cannot mutate it.
 */
final readonly class CartGiftInsert
{
    public function __construct(
        public string $sourceKey,       // stable; identifies which provider+rule produced this
        public string $description,
        public string $skuId,
        public int $quantity,
    ) {}
}
