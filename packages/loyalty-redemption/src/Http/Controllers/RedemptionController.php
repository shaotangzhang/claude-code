<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption\Http\Controllers;

use Acme\Cart\Models\Cart;
use Acme\LoyaltyRedemption\Services\LoyaltyRedemptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class RedemptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function apply(Request $request, LoyaltyRedemptionService $svc, Cart $cart): RedirectResponse
    {
        $request->validate(['points' => 'required|integer|min:1']);

        try {
            $svc->apply($cart, (int) $request->input('points'));
        } catch (\Throwable $e) {
            return back()->withErrors(['points' => $e->getMessage()]);
        }

        return back()->with('status', 'Points applied.');
    }

    public function clear(LoyaltyRedemptionService $svc, Cart $cart): RedirectResponse
    {
        $svc->clear($cart);

        return back()->with('status', 'Redemption cleared.');
    }
}
