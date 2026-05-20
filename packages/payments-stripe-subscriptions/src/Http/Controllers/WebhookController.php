<?php

declare(strict_types=1);

namespace Acme\PaymentsStripeSubscriptions\Http\Controllers;

use Acme\Membership\Events\PaymentReceived;
use Acme\PaymentsStripe\StripeSignature;
use Acme\PaymentsStripeSubscriptions\Models\StripeLink;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use RuntimeException;

/**
 * Handles Stripe Subscription webhooks. The signing secret is separate
 * from the one used by payments-stripe — register a different webhook
 * endpoint in your Stripe dashboard pointing at /payments/stripe-subs/webhook
 * and copy its signing secret to STRIPE_SUBS_WEBHOOK_SECRET.
 *
 * Events handled:
 *   customer.subscription.created  → store stripe_subscription_id
 *   customer.subscription.updated  → status + current_period_end refresh
 *   customer.subscription.deleted  → status=canceled
 *   invoice.payment_succeeded      → dispatch membership PaymentReceived
 *   invoice.payment_failed         → status=past_due (membership tick will pick up)
 */
final class WebhookController extends Controller
{
    public function __invoke(Request $request, Dispatcher $events): Response
    {
        $raw    = $request->getContent();
        $secret = (string) config('acme.payments-stripe-subscriptions.webhook_secret', '');
        $sig    = (string) $request->header('Stripe-Signature', '');

        if ($secret !== '') {
            if ($sig === '') {
                throw new RuntimeException('Stripe-Subs webhook: signature header missing.');
            }
            StripeSignature::verify($raw, $sig, $secret);
        }

        $payload = json_decode($raw, true) ?: [];
        $type    = (string) ($payload['type'] ?? '');
        $obj     = (array)  ($payload['data']['object'] ?? []);

        $link = $this->resolveLink($obj);
        if (! $link) {
            return new Response('OK');  // unknown subscription — ignore quietly
        }

        match ($type) {
            'customer.subscription.created'  => $this->onCreated($link, $obj),
            'customer.subscription.updated'  => $this->onUpdated($link, $obj),
            'customer.subscription.deleted'  => $this->onDeleted($link, $obj),
            'invoice.payment_succeeded'      => $this->onPaid($link, $obj, $events),
            'invoice.payment_failed'         => $this->onPastDue($link, $obj),
            default => null,
        };

        return new Response('OK');
    }

    private function resolveLink(array $obj): ?StripeLink
    {
        // For subscription events the obj IS the subscription.
        if (isset($obj['object']) && $obj['object'] === 'subscription') {
            return StripeLink::query()->where('stripe_subscription_id', (string) ($obj['id'] ?? ''))->first()
                ?? $this->byMetadata($obj);
        }

        // For invoice events: obj.subscription points to the subscription id.
        if (! empty($obj['subscription'])) {
            return StripeLink::query()->where('stripe_subscription_id', (string) $obj['subscription'])->first();
        }

        return $this->byMetadata($obj);
    }

    private function byMetadata(array $obj): ?StripeLink
    {
        $meta = (array) ($obj['metadata'] ?? []);
        $subId = (string) ($meta['acme_subscription_id'] ?? '');
        if ($subId === '') {
            return null;
        }

        return StripeLink::query()->where('subscription_id', $subId)->first();
    }

    private function onCreated(StripeLink $link, array $obj): void
    {
        $link->stripe_subscription_id = (string) ($obj['id'] ?? $link->stripe_subscription_id);
        $link->status                 = (string) ($obj['status'] ?? 'active');
        if (! empty($obj['current_period_end'])) {
            $link->current_period_end = CarbonImmutable::createFromTimestamp((int) $obj['current_period_end']);
        }
        $link->save();
    }

    private function onUpdated(StripeLink $link, array $obj): void
    {
        $link->status = (string) ($obj['status'] ?? $link->status);
        if (! empty($obj['current_period_end'])) {
            $link->current_period_end = CarbonImmutable::createFromTimestamp((int) $obj['current_period_end']);
        }
        $link->save();
    }

    private function onDeleted(StripeLink $link, array $obj): void
    {
        $link->status = 'canceled';
        $link->save();
    }

    private function onPaid(StripeLink $link, array $invoice, Dispatcher $events): void
    {
        // Idempotency: skip if this exact invoice already recorded.
        $invoiceId = (string) ($invoice['id'] ?? '');
        if ($invoiceId !== '' && $link->last_invoice_id === $invoiceId) {
            return;
        }
        $link->last_invoice_id = $invoiceId;
        $link->save();

        $events->dispatch(new PaymentReceived(
            subscriptionId: $link->subscription_id,
            amountCents:    (int) ($invoice['amount_paid'] ?? 0),
            currency:       strtoupper((string) ($invoice['currency'] ?? 'USD')),
            referenceId:    $invoiceId ?: 'stripe-invoice',
            receivedAtIso:  CarbonImmutable::now()->toIso8601String(),
        ));
    }

    private function onPastDue(StripeLink $link, array $invoice): void
    {
        $link->status = 'past_due';
        $link->save();
        // membership::tick will mark the underlying Subscription past_due
        // on its next pass; no need to forge a state change here.
    }
}
