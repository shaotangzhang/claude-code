<?php

declare(strict_types=1);

namespace Acme\Contracts\Payments;

final readonly class PaymentResult
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED    = 'failed';

    public function __construct(
        public string $status,           // pending | succeeded | failed
        public ?string $redirectUrl = null,
        public ?string $clientSecret = null,
        public ?string $gatewayReference = null,  // gateway-side id we'll quote in webhooks
        public ?string $failureReason = null,
        public array $raw = [],
    ) {}
}
