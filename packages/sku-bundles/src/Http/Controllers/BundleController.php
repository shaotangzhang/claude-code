<?php

declare(strict_types=1);

namespace Acme\SkuBundles\Http\Controllers;

use Acme\Cart\Http\Middleware\CartIdentifier;
use Acme\Cart\Models\Cart;
use Acme\SkuBundles\Models\Bundle;
use Acme\SkuBundles\Services\BundleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class BundleController extends Controller
{
    public function add(Request $request, BundleService $svc, Cart $cart): RedirectResponse
    {
        $request->validate(['bundle_id' => 'required|string']);
        $bundle = Bundle::query()->where('active', true)->findOrFail((string) $request->input('bundle_id'));

        try {
            $svc->addToCart($cart, $bundle);
        } catch (\Throwable $e) {
            return back()->withErrors(['bundle' => $e->getMessage()]);
        }

        return redirect(url(config('acme.cart.route_prefix', 'cart')))
            ->with('status', "Bundle '{$bundle->name}' added.");
    }

    public function remove(BundleService $svc, Cart $cart, string $sourceKey): RedirectResponse
    {
        $svc->removeFromCart($cart, $sourceKey);

        return back()->with('status', 'Bundle removed.');
    }

    public static function middleware(): array
    {
        return [CartIdentifier::class];
    }
}
