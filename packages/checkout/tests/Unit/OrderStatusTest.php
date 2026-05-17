<?php

declare(strict_types=1);

namespace Acme\Checkout\Tests\Unit;

use Acme\Checkout\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function test_terminal_statuses(): void
    {
        $this->assertTrue(OrderStatus::Fulfilled->isTerminal());
        $this->assertTrue(OrderStatus::Canceled->isTerminal());
        $this->assertTrue(OrderStatus::Refunded->isTerminal());

        $this->assertFalse(OrderStatus::PendingPayment->isTerminal());
        $this->assertFalse(OrderStatus::Paid->isTerminal());
        $this->assertFalse(OrderStatus::FailedPayment->isTerminal());
    }

    public function test_paid_statuses(): void
    {
        $this->assertTrue(OrderStatus::Paid->isPaid());
        $this->assertTrue(OrderStatus::Fulfilled->isPaid());

        $this->assertFalse(OrderStatus::PendingPayment->isPaid());
        $this->assertFalse(OrderStatus::Canceled->isPaid());
        $this->assertFalse(OrderStatus::Refunded->isPaid());
    }
}
