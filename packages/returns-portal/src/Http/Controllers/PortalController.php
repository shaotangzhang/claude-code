<?php

declare(strict_types=1);

namespace Acme\ReturnsPortal\Http\Controllers;

use Acme\Checkout\Models\Order;
use Acme\Commerce\Models\ReturnRequest;
use Acme\Commerce\Services\ReturnService;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(UserResolver $users): Response
    {
        $userId = (string) $users->currentUserId();
        $rmas   = ReturnRequest::query()
            ->where('user_id', $userId)
            ->with('items.orderItem')
            ->latest('requested_at')
            ->paginate(20);

        return new Response((string) view('acme-returns-portal::index', compact('rmas'))->render());
    }

    public function show(UserResolver $users, ReturnRequest $return): Response
    {
        abort_unless($return->user_id === $users->currentUserId(), 403);
        $return->loadMissing('items.orderItem');

        return new Response((string) view('acme-returns-portal::show', ['rma' => $return])->render());
    }

    public function create(UserResolver $users, string $orderId): Response
    {
        $order = $this->ownOrder($users, $orderId);
        $order->loadMissing('items');

        return new Response((string) view('acme-returns-portal::create', compact('order'))->render());
    }

    public function store(Request $request, UserResolver $users, ReturnService $svc, string $orderId): RedirectResponse
    {
        $order = $this->ownOrder($users, $orderId);

        $validated = $request->validate([
            'reason'             => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.order_item_id' => 'required|string',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.condition'  => 'nullable|string|max:40',
            'items.*.reason'     => 'nullable|string|max:255',
        ]);

        $orderItemIds = $order->items->pluck('id')->all();
        $items = collect($validated['items'])
            ->filter(fn ($i) => in_array($i['order_item_id'], $orderItemIds, true))
            ->values()->all();

        if (! $items) {
            return back()->withErrors(['items' => 'Pick at least one line that belongs to this order.']);
        }

        try {
            $rma = $svc->request($order, $users->currentUserId(), $items, $validated['reason'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['rma' => $e->getMessage()]);
        }

        return redirect()->route('acme.returns-portal.show', $rma)
            ->with('status', "Return {$rma->number} submitted.");
    }

    private function ownOrder(UserResolver $users, string $orderId): Order
    {
        $userId = $users->currentUserId();
        $order  = Order::query()->find($orderId);
        if (! $order || $order->user_id !== $userId) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $order;
    }
}
