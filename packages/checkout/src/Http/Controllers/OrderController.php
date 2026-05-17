<?php

declare(strict_types=1);

namespace Acme\Checkout\Http\Controllers;

use Acme\Checkout\Models\Order;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class OrderController extends Controller
{
    public function index(UserResolver $users): Response
    {
        $userId = $users->currentUserId();
        $orders = $userId
            ? Order::forUser($userId)->latest()->paginate(20)
            : Order::query()->whereRaw('1 = 0')->paginate(20);

        return new Response((string) view('acme-checkout::orders.index', compact('orders'))->render());
    }

    public function show(UserResolver $users, Order $order): Response
    {
        abort_unless($order->user_id === $users->currentUserId() || $users->currentUserCan('order.view'), 403);

        $order->loadMissing(['items', 'invoice']);

        return new Response((string) view('acme-checkout::orders.show', compact('order'))->render());
    }
}
