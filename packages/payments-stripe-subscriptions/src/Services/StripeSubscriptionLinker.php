<?php

declare(strict_types=1);

namespace Acme\PaymentsStripeSubscriptions\Services;

use Acme\Auth\Models\User;
use Acme\Membership\Models\Subscription;
use Acme\PaymentsStripeSubscriptions\Models\StripeLink;
use Acme\PaymentsStripeSubscriptions\StripeSubscriptionClient;
use RuntimeException;

/**
 * Ensures every acme/membership Subscription has a corresponding Stripe
 * Subscription. First call provisions a customer + checkout session
 * pointing at a Stripe Price configured on the membership Plan's
 * meta_json['stripe_price_id'].
 *
 * After the user pays the first invoice in Stripe's hosted Checkout,
 * webhooks (customer.subscription.created / invoice.payment_succeeded)
 * keep the StripeLink row in sync — and dispatch PaymentReceived to
 * membership so it advances the period.
 */
final class StripeSubscriptionLinker
{
    public function __construct(private readonly StripeSubscriptionClient $client) {}

    public function linkFor(Subscription $sub): StripeLink
    {
        return StripeLink::firstOrCreate(['subscription_id' => $sub->id]);
    }

    /**
     * Build a Stripe Checkout Session for first-time subscription.
     * Returns the redirect URL the user must follow.
     */
    public function startCheckout(Subscription $sub, ?string $returnUrl = null): string
    {
        $sub->loadMissing(['plan', 'user']);
        $plan = $sub->plan ?? throw new RuntimeException("Subscription {$sub->id} has no plan.");
        $user = $sub->user ?? User::query()->find($sub->user_id);

        $priceId = (string) (($plan->meta_json['stripe_price_id'] ?? ''));
        if ($priceId === '') {
            throw new RuntimeException("Plan {$plan->key} is missing meta_json.stripe_price_id (the Stripe Price ID).");
        }

        $link = $this->linkFor($sub);

        // First-time customer creation; cache id.
        if (! $link->stripe_customer_id) {
            $customer = $this->client->createCustomer([
                'email'             => (string) ($user?->email ?? ''),
                'name'              => (string) ($user?->name  ?? ''),
                'metadata[user_id]' => (string) $sub->user_id,
            ]);
            $link->stripe_customer_id = (string) ($customer['id'] ?? '');
            $link->stripe_price_id    = $priceId;
            $link->save();
        }

        $session = $this->client->createSubscriptionCheckoutSession([
            'mode'                                => 'subscription',
            'customer'                            => $link->stripe_customer_id,
            'line_items[0][price]'                => $priceId,
            'line_items[0][quantity]'             => 1,
            'client_reference_id'                 => $sub->id,
            'success_url'                         => $returnUrl ?? config('acme.payments-stripe-subscriptions.success_url', ''),
            'cancel_url'                          => (string) config('acme.payments-stripe-subscriptions.cancel_url', ''),
            'metadata[acme_subscription_id]'      => $sub->id,
        ]);

        return (string) ($session['url'] ?? '');
    }
}
