<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Listeners;

use Acme\AbandonedCart\Events\CartAbandoned;
use Acme\Notifications\Dispatcher;

final class SendRecoveryReminder
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function handle(CartAbandoned $event): void
    {
        $amount   = number_format($event->totalCents / 100, 2);
        $itemWord = $event->itemCount === 1 ? 'item' : 'items';

        $this->dispatcher->dispatch('cart.abandoned', [
            'user_id'   => $event->userId,
            'recipient' => $event->email,
            'subject'   => 'Your cart is waiting',
            'body_text' => "You left {$event->itemCount} {$itemWord} in your cart (total {$event->currency} {$amount}).\n\n"
                         . "Pick up where you left off:\n{$event->recoveryUrl}",
        ]);
    }
}
