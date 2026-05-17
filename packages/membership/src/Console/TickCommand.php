<?php

declare(strict_types=1);

namespace Acme\Membership\Console;

use Acme\Membership\Enums\SubscriptionStatus;
use Acme\Membership\Models\Subscription;
use Acme\Membership\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Idempotent state-machine advancer. Run on a 5-15 minute schedule.
 *
 *   active + period_end <= now  → emit PaymentDue (mark past_due)
 *   past_due + > grace_days     → expire
 *   trialing + trial_ends < now → either convert to active (emit
 *                                  PaymentDue) or expire (if plan is paid)
 *   paused + paused_until < now → auto-resume
 *   cancel_at_period_end + done → cancel immediately
 */
final class TickCommand extends Command
{
    protected $signature   = 'acme:membership:tick {--dry-run}';
    protected $description = 'Advance subscription state machines and emit PaymentDue / SubscriptionExpired as appropriate.';

    public function handle(SubscriptionService $svc): int
    {
        $now    = CarbonImmutable::now();
        $grace  = (int) config('acme.membership.grace_days', 7);
        $dry    = (bool) $this->option('dry-run');
        $counts = ['expired' => 0, 'due' => 0, 'resumed' => 0, 'canceled' => 0, 'trial_converted' => 0];

        Subscription::query()->whereIn('status', [
            SubscriptionStatus::Active->value,
            SubscriptionStatus::Trialing->value,
            SubscriptionStatus::PastDue->value,
            SubscriptionStatus::Paused->value,
        ])->with('plan.tier')->chunkById(200, function ($subs) use ($svc, $now, $grace, $dry, &$counts): void {
            foreach ($subs as $sub) {
                $this->advance($sub, $svc, $now, $grace, $dry, $counts);
            }
        });

        $this->table(['transition', 'count'], collect($counts)->map(fn ($v, $k) => [$k, $v])->values()->all());

        return self::SUCCESS;
    }

    private function advance(
        Subscription $sub,
        SubscriptionService $svc,
        CarbonImmutable $now,
        int $grace,
        bool $dry,
        array &$counts,
    ): void {
        // Trial expired? Convert (free plan) or emit PaymentDue (paid plan).
        if ($sub->status === SubscriptionStatus::Trialing && $sub->trial_ends_at?->lt($now)) {
            if ($sub->plan->price_cents === 0) {
                if (! $dry) {
                    $sub->status = SubscriptionStatus::Active;
                    $sub->save();
                }
            } else {
                if (! $dry) {
                    $svc->emitPaymentDue($sub, $sub->plan, $now, isInitial: true);
                    $sub->status = SubscriptionStatus::PastDue;
                    $sub->save();
                }
            }
            $counts['trial_converted']++;
            return;
        }

        // Cancel-at-period-end honoring
        if ($sub->cancel_at_period_end && $sub->current_period_end?->lte($now)) {
            if (! $dry) { $svc->cancel($sub, atPeriodEnd: false); }
            $counts['canceled']++;
            return;
        }

        // Active period ended — push to past_due and emit PaymentDue.
        if ($sub->status === SubscriptionStatus::Active && $sub->current_period_end?->lte($now)) {
            if (! $dry) {
                if (config('acme.membership.auto_renew_no_payment')) {
                    $svc->recordPayment($sub, $now);
                } else {
                    $svc->emitPaymentDue($sub, $sub->plan, $now, isInitial: false);
                    $sub->status = SubscriptionStatus::PastDue;
                    $sub->save();
                }
            }
            $counts['due']++;
            return;
        }

        // Past-due grace exceeded → expire.
        if ($sub->status === SubscriptionStatus::PastDue
            && $sub->current_period_end?->copy()->addDays($grace)->lte($now)) {
            if (! $dry) { $svc->expire($sub); }
            $counts['expired']++;
            return;
        }

        // Paused until expired → resume.
        if ($sub->status === SubscriptionStatus::Paused
            && $sub->paused_until !== null && $sub->paused_until->lte($now)) {
            if (! $dry) { $svc->resume($sub); }
            $counts['resumed']++;
            return;
        }
    }
}
