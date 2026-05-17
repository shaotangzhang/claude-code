<?php

declare(strict_types=1);

namespace Acme\Wishlist\Http\Controllers;

use Acme\Cart\Http\Middleware\CartIdentifier;
use Acme\Cart\Models\Cart;
use Acme\Catalog\Models\Sku;
use Acme\Contracts\Auth\UserResolver;
use Acme\Wishlist\Models\WishlistItem;
use Acme\Wishlist\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(UserResolver $users, WishlistService $svc): Response
    {
        $userId = $users->currentUserId();
        $list   = $svc->defaultListFor((string) $userId);
        $list->loadMissing(['items.sku.product']);

        return new Response((string) view('acme-wishlist::show', ['list' => $list])->render());
    }

    public function addItem(Request $request, UserResolver $users, WishlistService $svc): RedirectResponse
    {
        $request->validate(['sku_id' => 'required|string', 'note' => 'nullable|string|max:255']);

        $sku = Sku::query()->findOrFail((string) $request->input('sku_id'));
        $svc->addItem((string) $users->currentUserId(), $sku, note: $request->input('note'));

        return back()->with('status', 'Added to wishlist.');
    }

    public function removeItem(UserResolver $users, WishlistService $svc, WishlistItem $item): RedirectResponse
    {
        $svc->removeItem($item, (string) $users->currentUserId());

        return back();
    }

    public function moveToCart(Request $request, UserResolver $users, WishlistService $svc, WishlistItem $item): RedirectResponse
    {
        // Cart resolution requires the cart middleware to have run first.
        $cart = app(Cart::class);
        $svc->moveToCart(
            item:           $item,
            userId:         (string) $users->currentUserId(),
            cart:           $cart,
            quantity:       max(1, (int) $request->input('quantity', 1)),
            keepInWishlist: (bool) $request->boolean('keep'),
        );

        return redirect(url(config('acme.cart.route_prefix', 'cart')))->with('status', 'Moved to cart.');
    }

    public static function moveToCartMiddleware(): array
    {
        return [CartIdentifier::class];
    }
}
