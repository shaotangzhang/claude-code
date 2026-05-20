<?php

declare(strict_types=1);

namespace Acme\Payments\Services;

use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use Acme\Payments\Events\PaymentFailed;
use Acme\Payments\Events\PaymentRefunded;
use Acme\Payments\Events\PaymentSucceeded;
use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Payments\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * The ledger-side of payments. Records transactions, drives state
 * transitions, fires public events. Gateway implementations stay
 * stateless — all persistence happens here.
 */
final class PaymentService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly GatewayRegistry $gateways,
    ) {}

    public function createIntent(
        ?string $userId,
        string $relatedType,
        string $relatedId,
        int $amountCents,
        string $currency,
        ?string $gatewayKey = null,
        ?string $returnUrl = null,
        ?string $description = null,
        array $metadata = [],
    ): array {
        $key     = $gatewayKey ?? (string) config('acme.payments.default_gateway', 'manual');
        $gateway = $this->gateways->resolve($key);

        return DB::transaction(function () use (
            $userId, $relatedType, $relatedId, $amountCents, $currency,
            $key, $returnUrl, $description, $metadata, $gateway
        ): array {
            $tx = Transaction::create([
                'user_id'      => $userId,
                'gateway'      => $key,
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
                'amount_cents' => $amountCents,
                'currency'     => $currency,
                'status'       => Transaction::STATUS_PENDING,
                'payload_json' => ['metadata' => $metadata, 'description' => $description],
            ]);

            $result = $gateway->createIntent(new PaymentIntent(
                transactionId: $tx->id,
                amountCents:   $amountCents,
                currency:      $currency,
                relatedType:   $relatedType,
                relatedId:     $relatedId,
                returnUrl:     $returnUrl,
                description:   $description,
                metadata:      $metadata,
            ));

            $tx->gateway_reference = $result->gatewayReference;
            $tx->save();

            // If the gateway resolves synchronously (e.g. wallet pay) mark
            // it succeeded right now.
            if ($result->status === PaymentResult::STATUS_SUCCEEDED) {
                $this->markSucceeded($tx);
            } elseif ($result->status === PaymentResult::STATUS_FAILED) {
                $this->markFailed($tx, $result->failureReason ?? 'gateway returned failed');
            }

            return ['transaction' => $tx->fresh(), 'result' => $result];
        });
    }

    public function markSucceeded(Transaction $tx): Transaction
    {
        if ($tx->status === Transaction::STATUS_SUCCEEDED) {
            return $tx; // idempotent
        }

        $tx->status       = Transaction::STATUS_SUCCEEDED;
        $tx->succeeded_at = CarbonImmutable::now();
        $tx->save();

        $this->events->dispatch(new PaymentSucceeded(
            transactionId:    $tx->id,
            gatewayKey:       $tx->gateway,
            gatewayReference: $tx->gateway_reference,
            relatedType:      $tx->related_type,
            relatedId:        $tx->related_id,
            amountCents:      $tx->amount_cents,
            currency:         $tx->currency,
            succeededAtIso:   $tx->succeeded_at->toIso8601String(),
        ));

        return $tx;
    }

    public function markFailed(Transaction $tx, string $reason): Transaction
    {
        if ($tx->status === Transaction::STATUS_FAILED) {
            return $tx;
        }

        $tx->status         = Transaction::STATUS_FAILED;
        $tx->failure_reason = $reason;
        $tx->failed_at      = CarbonImmutable::now();
        $tx->save();

        $this->events->dispatch(new PaymentFailed(
            transactionId: $tx->id,
            gatewayKey:    $tx->gateway,
            relatedType:   $tx->related_type,
            relatedId:     $tx->related_id,
            reason:        $reason,
        ));

        return $tx;
    }

    public function refund(Transaction $tx, ?int $amountCents = null): Transaction
    {
        $gateway = $this->gateways->resolve($tx->gateway);
        $amount  = $amountCents ?? $tx->amount_cents;

        $result = $gateway->refund((string) $tx->gateway_reference, $amount, $tx->currency);
        if ($result->status !== PaymentResult::STATUS_SUCCEEDED) {
            throw new \RuntimeException("Refund did not succeed at gateway: {$tx->gateway}");
        }

        return $this->markRefunded($tx, $amount);
    }

    /**
     * Record a refund whose state of truth is the gateway's webhook —
     * i.e. someone clicked "refund" in the Stripe dashboard and we
     * learn about it second-hand. Idempotent on duplicate webhooks.
     */
    public function markRefunded(Transaction $tx, int $amountCents): Transaction
    {
        if ($tx->status === Transaction::STATUS_REFUNDED) {
            return $tx;
        }

        $tx->status      = Transaction::STATUS_REFUNDED;
        $tx->refunded_at = CarbonImmutable::now();
        $tx->save();

        $this->events->dispatch(new PaymentRefunded(
            transactionId: $tx->id,
            gatewayKey:    $tx->gateway,
            relatedType:   $tx->related_type,
            relatedId:     $tx->related_id,
            amountCents:   $amountCents,
            currency:      $tx->currency,
        ));

        return $tx;
    }
}
