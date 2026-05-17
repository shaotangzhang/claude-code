<?php

declare(strict_types=1);

namespace Acme\Cart\Http\Controllers;

use Acme\Cart\Models\Cart;
use Acme\Cart\Models\CartItem;
use Acme\Cart\Services\CartService;
use Acme\Catalog\Models\Sku;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class CartController extends Controller
{
    public function show(Cart $cart): Response
    {
        $cart->loadMissing(['items.sku.product', 'coupons']);

        return new Response((string) view('acme-cart::cart', ['cart' => $cart])->render());
    }

    public function addItem(Request $request, CartService $service, Cart $cart): RedirectResponse
    {
        $request->validate([
            'sku_id'   => 'required|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $sku = Sku::query()->findOrFail((string) $request->input('sku_id'));
        $service->addItem($cart, $sku, (int) $request->input('quantity', 1));

        return back()->with('status', 'Added to cart.');
    }

    public function updateItem(Request $request, CartService $service, CartItem $item): RedirectResponse
    {
        $request->validate(['quantity' => 'required|integer|min:0']);
        $this->authorizeOwnership($item);

        $service->updateQuantity($item, (int) $request->input('quantity'));

        return back();
    }

    public function removeItem(CartService $service, CartItem $item): RedirectResponse
    {
        $this->authorizeOwnership($item);
        $service->removeItem($item);

        return back();
    }

    private function authorizeOwnership(CartItem $item): void
    {
        $cart = app(Cart::class);
        abort_unless($item->cart_id === $cart->id, 403);
    }
}
