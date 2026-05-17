<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Console;

use Acme\AbandonedCart\Services\AbandonmentService;
use Acme\Cart\Models\Cart;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Periodic sweep. Run on a schedule (every 10–30 min in production).
 *
 *   active + has non-gift items + updated_at < now - idle_hours
 *     → AbandonmentService::mark() (status=abandoned, emit event,
 *                                    listener fans out notifications)
 */
final class TickCommand extends Command
{
    protected $signature   = 'acme:abandoned-cart:tick {--dry-run}';
    protected $description = 'Find idle non-empty carts, mark abandoned, emit recovery notifications.';

    public function handle(AbandonmentService $svc): int
    {
        $idleHours = (int) config('acme.abandoned-cart.idle_hours', 24);
        $minItems  = (int) config('acme.abandoned-cart.min_items', 1);
        $limit     = (int) config('acme.abandoned-cart.batch_limit', 200);
        $dry       = (bool) $this->option('dry-run');

        $cutoff = CarbonImmutable::now()->subHours($idleHours);

        $candidates = Cart::query()
            ->where('status', Cart::STATUS_ACTIVE)
            ->where('updated_at', '<', $cutoff)
            ->whereHas('items', fn ($q) => $q->where('is_gift', false), '>=', $minItems)
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $marked = 0;
        foreach ($candidates as $cart) {
            if ($dry) {
                $this->line("[dry] would mark cart {$cart->id} (user={$cart->user_id})");
                continue;
            }
            $svc->mark($cart);
            $marked++;
        }

        $this->info(($dry ? '[dry] ' : '') . "Marked {$marked} cart(s) abandoned " .
                    "(cutoff < {$cutoff->toIso8601String()}).");

        return self::SUCCESS;
    }
}
