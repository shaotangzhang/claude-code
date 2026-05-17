<?php

declare(strict_types=1);

namespace Acme\Payments\Gateways;

use Acme\Contracts\Payments\PaymentGateway;
use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use RuntimeException;

/**
 * "Pay offline" gateway — bank transfer, COD, or any flow where a human
 * later marks the transaction succeeded. createIntent returns pending;
 * /admin/payments/transactions/{id}/confirm triggers the success path.
 *
 * Use this as the reference implementation. Real gateway packages
 * (acme/payments-stripe etc.) follow the same shape.
 */
final class ManualGateway implements PaymentGateway
{
    public function key(): string { return 'manual'; }

    public function createIntent(PaymentIntent $intent): PaymentResult
    {
        return new PaymentResult(
            status:           PaymentResult::STATUS_PENDING,
            gatewayReference: 'manual_' . $intent->transactionId,
            raw:              ['note' => 'awaiting offline confirmation'],
        );
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        // Manual gateway doesn't receive external webhooks. The admin route
        // calls PaymentService::markSucceeded directly.
        throw new RuntimeException('Manual gateway has no webhook endpoint.');
    }

    public function refund(string $gatewayReference, int $amountCents, string $currency): PaymentResult
    {
        // Manual refunds are recorded but acted on outside the system.
        return new PaymentResult(
            status:           PaymentResult::STATUS_SUCCEEDED,
            gatewayReference: $gatewayReference,
            raw:              ['mode' => 'manual'],
        );
    }
}
