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

        $tx = Transaction::query()->findOrFail($parsed['transaction_id']);

        match ($parsed['status']) {
            'succeeded' => $svc->markSucceeded($tx),
            'failed'    => $svc->markFailed($tx, (string) ($parsed['raw']['reason'] ?? 'failed')),
            default     => null,
        };

        return new Response('OK');
    }
}
