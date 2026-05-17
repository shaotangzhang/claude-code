<?php

declare(strict_types=1);

namespace Acme\Membership\Services;

use Acme\Membership\Enums\SubscriptionStatus;
use Acme\Membership\Events\PaymentDue;
use Acme\Membership\Events\SubscriptionCanceled;
use Acme\Membership\Events\SubscriptionExpired;
use Acme\Membership\Events\SubscriptionPaused;
use Acme\Membership\Events\SubscriptionRenewed;
use Acme\Membership\Events\SubscriptionResumed;
use Acme\Membership\Events\SubscriptionStarted;
use Acme\Membership\Models\Plan;
use Acme\Membership\Models\Subscription;
use Acme\Membership\Models\SubscriptionEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * State machine for a Subscription. Every transition is wrapped in a
 * transaction, logs an audit row in subscription_events, and dispatches
 * a public event for downstream packages.
 *
 * Payment integration is intentionally out of scope — at the boundary
 * we only emit PaymentDue and consume PaymentReceived.
 */
final class SubscriptionService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function start(string $userId, Plan $plan, ?CarbonInterface $now = null): Subscription
    {
        $now ??= CarbonImmutable::now();

        return DB::transaction(function () use ($userId, $plan, $now): Subscription {
            $trialing  = $plan->trial_days > 0;
            $trialEnds = $trialing ? $now->copy()->addDays($plan->trial_days) : null;

            $periodStart = $now;
            $periodEnd   = $plan->billing_period->advance($trialEnds ?? $now);

            $sub = Subscription::create([
                'user_id'              => $userId,
                'plan_id'              => $plan->id,
                'status'               => $trialing ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
                'started_at'           => $now,
                'current_period_start' => $periodStart,
                'current_period_end'   => $periodEnd,
                'trial_ends_at'        => $trialEnds,
            ]);

            $this->log($sub, 'started', ['trialing' => $trialing]);
            $this->events->dispatch(new SubscriptionStarted(
                subscriptionId: $sub->id,
                userId:         $userId,
                planKey:        $plan->key,
                tierKey:        $plan->tier->key,
                isTrialing:     $trialing,
            ));

            // Free plan? no payment needed.
            if ($plan->price_cents > 0 && ! $trialing) {
                $this->emitPaymentDue($sub, $plan, $now, isInitial: true);
            }

            return $sub;
        });
    }

    /** Called when an external billing package confirms a charge. */
    public function recordPayment(Subscription $sub, ?CarbonInterface $now = null): void
    {
        $now ??= CarbonImmutable::now();

        DB::transaction(function () use ($sub, $now): void {
            $plan = $sub->plan;
            $base = $sub->current_period_end && $sub->current_period_end->isFuture()
                ? $sub->current_period_end
                : $now;

            $sub->status               = SubscriptionStatus::Active;
            $sub->current_period_start = $base;
            $sub->current_period_end   = $plan->billing_period->advance($base);
            $sub->save();

            $this->log($sub, 'payment_received');
            $this->log($sub, 'renewed', ['new_period_end' => $sub->current_period_end->toIso8601String()]);
        });

        $this->events->dispatch(new SubscriptionRenewed(
            subscriptionId:  $sub->id,
            userId:          $sub->user_id,
            newPeriodEndIso: $sub->current_period_end->toIso8601String(),
        ));
    }

    public function pause(Subscription $sub, ?CarbonInterface $until = null): void
    {
        if (! in_array($sub->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)) {
            throw new RuntimeException("Can only pause active/trialing subscriptions; current: {$sub->status->value}");
        }

        $sub->status       = SubscriptionStatus::Paused;
        $sub->paused_at    = CarbonImmutable::now();
        $sub->paused_until = $until;
        $sub->save();

        $this->log($sub, 'paused', ['until' => $until?->toIso8601String()]);
        $this->events->dispatch(new SubscriptionPaused($sub->id, $sub->user_id, $until?->toIso8601String()));
    }

    public function resume(Subscription $sub): void
    {
        if ($sub->status !== SubscriptionStatus::Paused) {
            throw new RuntimeException("Cannot resume a non-paused subscription; current: {$sub->status->value}");
        }

        $sub->status       = SubscriptionStatus::Active;
        $sub->paused_at    = null;
        $sub->paused_until = null;
        $sub->save();

        $this->log($sub, 'resumed');
        $this->events->dispatch(new SubscriptionResumed($sub->id, $sub->user_id));
    }

    public function cancel(Subscription $sub, bool $atPeriodEnd = true): void
    {
        $sub->canceled_at = CarbonImmutable::now();
        if ($atPeriodEnd) {
            $sub->cancel_at_period_end = true;
        } else {
            $sub->status = SubscriptionStatus::Canceled;
        }
        $sub->save();

        $this->log($sub, 'canceled', ['immediate' => ! $atPeriodEnd]);
        $this->events->dispatch(new SubscriptionCanceled($sub->id, $sub->user_id, ! $atPeriodEnd));
    }

    public function expire(Subscription $sub): void
    {
        $sub->status = SubscriptionStatus::Expired;
        $sub->save();

        $this->log($sub, 'expired');
        $this->events->dispatch(new SubscriptionExpired($sub->id, $sub->user_id, $sub->plan->tier->key));
    }

    /** Used by the tick command when a period ends. */
    public function emitPaymentDue(Subscription $sub, Plan $plan, CarbonInterface $now, bool $isInitial = false): void
    {
        $this->log($sub, 'payment_due', ['initial' => $isInitial, 'amount_cents' => $plan->price_cents]);

        $this->events->dispatch(new PaymentDue(
            subscriptionId: $sub->id,
            userId:         $sub->user_id,
            planKey:        $plan->key,
            amountCents:    $plan->price_cents,
            currency:       $plan->currency,
            dueIso:         $now->toIso8601String(),
            isInitial:      $isInitial,
        ));
    }

    private function log(Subscription $sub, string $type, array $payload = []): void
    {
        SubscriptionEvent::create([
            'subscription_id' => $sub->id,
            'event_type'      => $type,
            'payload_json'    => $payload ?: null,
            'created_at'      => CarbonImmutable::now(),
        ]);
    }
}
