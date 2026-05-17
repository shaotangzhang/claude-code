<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Console;

use Acme\AbandonedCart\Services\AbandonmentService;
use Acme\Cart\Models\Cart;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Periodic sweep, two phases:
 *
 *   PHASE 1 (mark + round 1):
 *     active + non-gift items + updated_at < now - idle_hours
 *       → AbandonmentService::mark()
 *
 *   PHASE 2 (rounds 2..N):
 *     for each abandoned cart whose abandoned_at < now - round.hours_after_mark
 *     AND previous round already sent AND THIS round not yet sent
 *       → AbandonmentService::remind($round)
 *
 * Recommended schedule: every 10-30 min.
 */
final class TickCommand extends Command
{
    protected $signature   = 'acme:abandoned-cart:tick {--dry-run}';
    protected $description = 'Mark idle carts abandoned, fan out multi-round reminders with coupons.';

    public function handle(AbandonmentService $svc): int
    {
        $dry   = (bool) $this->option('dry-run');
        $limit = (int) config('acme.abandoned-cart.batch_limit', 200);
        $now   = CarbonImmutable::now();

        // PHASE 1: mark
        $idleHours = (int) config('acme.abandoned-cart.idle_hours', 24);
        $minItems  = (int) config('acme.abandoned-cart.min_items', 1);
        $cutoff    = $now->subHours($idleHours);

        $marked = 0;
        Cart::query()
            ->where('status', Cart::STATUS_ACTIVE)
            ->where('updated_at', '<', $cutoff)
            ->whereHas('items', fn ($q) => $q->where('is_gift', false), '>=', $minItems)
            ->orderBy('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (Cart $cart) use (&$marked, $svc, $dry): void {
                if ($dry) {
                    $this->line("[dry] mark cart {$cart->id}");
                } else {
                    $svc->mark($cart);
                }
                $marked++;
            });

        // PHASE 2: subsequent rounds
        $rounds = (array) config('acme.abandoned-cart.rounds', []);
        ksort($rounds);
        $reminded = 0;

        foreach ($rounds as $round => $cfg) {
            $round = (int) $round;
            if ($round < 2) {
                continue;
            }
            $hoursAfter = (int) ($cfg['hours_after_mark'] ?? 0);
            $thisCutoff = $now->subHours($hoursAfter);
            $prevRound  = $round - 1;

            $eligible = Cart::query()
                ->where('status', Cart::STATUS_ABANDONED)
                ->where('abandoned_at', '<=', $thisCutoff)
                ->whereExists(function ($q) use ($prevRound): void {
                    $q->from('acme_abandoned_reminders')
                      ->whereColumn('acme_abandoned_reminders.cart_id', 'acme_cart_carts.id')
                      ->where('round', $prevRound);
                })
                ->whereNotExists(function ($q) use ($round): void {
                    $q->from('acme_abandoned_reminders')
                      ->whereColumn('acme_abandoned_reminders.cart_id', 'acme_cart_carts.id')
                      ->where('round', $round);
                })
                ->limit($limit)
                ->get();

            foreach ($eligible as $cart) {
                if ($dry) {
                    $this->line("[dry] remind round {$round} cart {$cart->id}");
                } else {
                    $svc->remind($cart, $round);
                }
                $reminded++;
            }
        }

        $this->info(($dry ? '[dry] ' : '') . "Marked {$marked} | Reminded {$reminded}");

        return self::SUCCESS;
    }
}
