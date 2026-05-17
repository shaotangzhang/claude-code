<?php

declare(strict_types=1);

namespace Acme\SkuBundles\Events;

final readonly class BundleAddedToCart
{
    public function __construct(
        public string $cartId,
        public string $bundleKey,
        public string $sourceKey,   // "bundle:summer-pack:abc12345"
        public int $childLineCount,
    ) {}
}
