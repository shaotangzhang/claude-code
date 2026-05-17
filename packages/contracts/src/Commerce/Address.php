<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

final readonly class Address
{
    public function __construct(
        public string $country,    // ISO 3166-1 alpha-2
        public ?string $region = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $line1 = null,
        public ?string $line2 = null,
        public ?string $recipient = null,
        public ?string $phone = null,
    ) {}
}
