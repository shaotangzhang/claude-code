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

        $subject = match ($event->round) {
            1       => 'Your cart is waiting',
            2       => 'Still thinking? Here is 10% off',
            3       => 'Last chance — your cart expires soon',
            default => "Reminder · round {$event->round}",
        };

        $body = "You left {$event->itemCount} {$itemWord} in your cart "
              . "(total {$event->currency} {$amount}).\n\n"
              . "Pick up where you left off:\n{$event->recoveryUrl}";

        if ($event->couponCode !== null) {
            $body .= "\n\nUse code at checkout: " . $event->couponCode;
        }

        $this->dispatcher->dispatch('cart.abandoned', [
            'user_id'   => $event->userId,
            'recipient' => $event->email,
            'subject'   => $subject,
            'body_text' => $body,
        ]);
    }
}
