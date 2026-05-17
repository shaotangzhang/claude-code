<?php

declare(strict_types=1);

namespace Acme\Checkout\Services;

use Acme\Cart\Models\Cart;
use Acme\Cart\Services\CartService;
use Acme\Cart\Services\TotalsCalculator;
use Acme\Checkout\Enums\OrderStatus;
use Acme\Checkout\Events\OrderPlaced;
use Acme\Checkout\Models\Invoice;
use Acme\Checkout\Models\Order;
use Acme\Checkout\Models\OrderItem;
use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Auth\UserResolver;
use Acme\Payments\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Converts a Cart → Order → PaymentIntent. The cart is marked converted
 * inside the same transaction so the user can't double-submit.
 *
 * Order rows snapshot the cart at submit time — they survive SKU
 * deletion / price changes.
 */
final class CheckoutService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly UserResolver $users,
        private readonly TotalsCalculator $totals,
        private readonly CartService $carts,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @param  Address  $billing
     * @param  Address  $shipping
     * @param  string  $shippingOptionKey
     * @param  string  $gatewayKey  payment gateway to use
     * @param  string|null  $returnUrl  where the gateway should redirect on success
     * @return array{order: Order, payment: array{transaction: \Acme\Payments\Models\Transaction, result: \Acme\Contracts\Payments\PaymentResult}}
     */
    public function submit(
        Cart $cart,
        Address $billing,
        Address $shipping,
        string $shippingOptionKey,
        string $gatewayKey,
        ?string $returnUrl = null,
    ): array {
        $cart->loadMissing(['items.sku.product', 'coupons']);
        if ($cart->items->isEmpty()) {
            throw new RuntimeException("Cannot place an order from an empty cart.");
        }
        if ($cart->status !== Cart::STATUS_ACTIVE) {
            throw new RuntimeException("Cart is not active (status={$cart->status}).");
        }

        // Re-run totals with the chosen address + shipping option so the
        // order amount truly reflects what the user sees.
        $cart = $this->totals->recalculate($cart, $shipping, $shippingOptionKey);

        return DB::transaction(function () use ($cart, $billing, $shipping, $shippingOptionKey, $gatewayKey, $returnUrl): array {
            $userId = $this->users->currentUserId();

            $order = Order::create([
                'number'              => $this->generateNumber(),
                'user_id'             => $userId,
                'currency'            => $cart->currency,
                'status'              => OrderStatus::PendingPayment,
                'subtotal_cents'      => $cart->subtotal_cents,
                'discount_cents'      => $cart->discount_cents,
                'tax_cents'           => $cart->tax_cents,
                'shipping_cents'      => $cart->shipping_cents,
                'total_cents'         => $cart->total_cents,
                'billing_address'     => (array) $billing,
                'shipping_address'    => (array) $shipping,
                'shipping_option_key' => $shippingOptionKey,
                'payment_gateway'     => $gatewayKey,
                'placed_at'           => CarbonImmutable::now(),
            ]);

            foreach ($cart->items as $i) {
                OrderItem::create([
                    'order_id'         => $order->id,
                    'sku_id'           => $i->sku_id,
                    'sku_code'         => $i->sku?->code ?? 'UNKNOWN',
                    'product_title'    => $i->sku?->product?->title ?? '',
                    'quantity'         => $i->quantity,
                    'unit_price_cents' => $i->unit_price_cents,
                    'line_total_cents' => $i->line_total_cents,
                    'currency'         => $i->currency,
                    'attrs_json'       => $i->attrs_json,
                ]);
            }

            Invoice::create([
                'order_id' => $order->id,
                'number'   => 'INV-' . $order->number,
                'status'   => Invoice::STATUS_DRAFT,
            ]);

            $this->carts->markConverted($cart);

            $this->events->dispatch(new OrderPlaced(
                orderId:    $order->id,
                number:     $order->number,
                userId:     $userId,
                totalCents: $order->total_cents,
                currency:   $order->currency,
            ));

            $payment = $this->payments->createIntent(
                userId:      $userId,
                relatedType: 'order',
                relatedId:   $order->id,
                amountCents: $order->total_cents,
                currency:    $order->currency,
                gatewayKey:  $gatewayKey,
                returnUrl:   $returnUrl,
                description: "Order {$order->number}",
            );

            $order->payment_transaction_id = $payment['transaction']->id;
            $order->save();

            return ['order' => $order->fresh(['items']), 'payment' => $payment];
        });
    }

    private function generateNumber(): string
    {
        $prefix = (string) config('acme.checkout.order_number_prefix', '');
        $date   = now()->format('Ymd');
        $rand   = strtoupper(substr((string) Str::ulid(), -6));

        return "{$prefix}{$date}-{$rand}";
    }
}
