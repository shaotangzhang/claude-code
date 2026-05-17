<?php

declare(strict_types=1);

namespace Acme\Commerce\Events;

final readonly class ReviewSubmitted
{
    public function __construct(
        public string $reviewId,
        public string $productId,
        public ?string $userId,
        public int $rating,
        public string $status,
    ) {}
}
