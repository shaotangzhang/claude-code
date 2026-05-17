<?php

declare(strict_types=1);

namespace Acme\Checkout\Http\Controllers;

use Acme\Cart\Http\Middleware\CartIdentifier;
use Acme\Cart\Models\Cart;
use Acme\Checkout\Services\CheckoutService;
use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingCalculator;
use Acme\Payments\Gateways\GatewayRegistry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware(CartIdentifier::class);
        if (config('acme.checkout.require_login')) {
            $this->middleware('auth');
        }
    }

    public function show(Cart $cart, ShippingCalculator $shipping, GatewayRegistry $gateways): Response
    {
        $cart->loadMissing(['items.sku.product']);

        $shippingOptions = $shipping->options(
            $cart->items->map(fn ($i) => ['sku_id' => $i->sku_id, 'quantity' => $i->quantity])->all() + ['__subtotal_cents' => $cart->subtotal_cents],
            $cart->currency,
            null,
        );

        return new Response((string) view('acme-checkout::show', [
            'cart'            => $cart,
            'shippingOptions' => $shippingOptions,
            'gateways'        => array_keys($gateways->all()),
        ])->render());
    }

    public function place(Request $request, Cart $cart, CheckoutService $svc): RedirectResponse
    {
        $validated = $request->validate([
            'shipping.country'  => 'required|string|size:2',
            'shipping.region'   => 'nullable|string',
            'shipping.city'     => 'nullable|string',
            'shipping.postal_code' => 'nullable|string',
            'shipping.line1'    => 'nullable|string',
            'shipping.recipient' => 'required|string',
            'shipping.phone'    => 'nullable|string',
            'shipping_option_key' => 'required|string',
            'payment_gateway'   => 'required|string',
        ]);

        $address = new Address(
            country:    $validated['shipping']['country'],
            region:     $validated['shipping']['region']      ?? null,
            city:       $validated['shipping']['city']        ?? null,
            postalCode: $validated['shipping']['postal_code'] ?? null,
            line1:      $validated['shipping']['line1']       ?? null,
            recipient:  $validated['shipping']['recipient'],
            phone:      $validated['shipping']['phone']       ?? null,
        );

        $result = $svc->submit(
            cart:              $cart,
            billing:           $address,
            shipping:          $address,
            shippingOptionKey: $validated['shipping_option_key'],
            gatewayKey:        $validated['payment_gateway'],
            returnUrl:         route('acme.checkout.orders.show', ['order' => '__id__']),
        );

        if ($result['payment']['result']->redirectUrl) {
            return redirect()->away($result['payment']['result']->redirectUrl);
        }

        return redirect()->route('acme.checkout.orders.show', $result['order'])
            ->with('status', 'Order placed; awaiting payment.');
    }
}
