<?php

declare(strict_types=1);

namespace Acme\Payments\Http\Controllers;

use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Payments\Models\Transaction;
use Acme\Payments\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        GatewayRegistry $registry,
        PaymentService $svc,
        string $gateway,
    ): Response {
        if (! $registry->has($gateway)) {
            throw new NotFoundHttpException("Unknown gateway: {$gateway}");
        }

        $parsed = $registry->resolve($gateway)->parseWebhook(
            (array) $request->all(),
            $request->headers->all(),
        );

        // Most webhooks identify by transaction_id we stamped in metadata.
        // Refund / dispute webhooks may only quote the gateway's payment-intent
        // id; parseWebhook returns it under raw.lookup_by_reference so we can
        // locate the original transaction.
        $tx = ! empty($parsed['transaction_id'])
            ? Transaction::query()->find($parsed['transaction_id'])
            : null;
        if (! $tx && ! empty($parsed['raw']['lookup_by_reference'])) {
            $tx = Transaction::query()
                ->where('gateway', $gateway)
                ->where('gateway_reference', $parsed['raw']['lookup_by_reference'])
                ->first();
        }
        if (! $tx) {
            throw new NotFoundHttpException('Webhook target transaction not found.');
        }

        match ($parsed['status']) {
            'succeeded' => $svc->markSucceeded($tx),
            'failed'    => $svc->markFailed($tx, (string) ($parsed['raw']['reason'] ?? 'failed')),
            'refunded'  => $svc->markRefunded($tx, (int) ($parsed['raw']['amount_cents'] ?? $tx->amount_cents)),
            default     => null,
        };

        return new Response('OK');
    }
}
