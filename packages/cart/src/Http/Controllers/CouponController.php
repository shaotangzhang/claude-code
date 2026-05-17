<?php

declare(strict_types=1);

namespace Acme\Cart\Http\Controllers;

use Acme\Cart\Models\Cart;
use Acme\Cart\Models\Coupon;
use Acme\Cart\Services\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CouponController extends Controller
{
    public function apply(Request $request, CouponService $svc, Cart $cart): RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:64']);

        try {
            $svc->apply($cart, (string) $request->input('code'));
        } catch (\Throwable $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'Coupon applied.');
    }

    public function remove(CouponService $svc, Cart $cart, Coupon $coupon): RedirectResponse
    {
        $svc->remove($cart, $coupon);

        return back();
    }
}
