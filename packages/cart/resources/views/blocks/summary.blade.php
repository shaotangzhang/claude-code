<a class="cart-summary" href="{{ url(config('acme.cart.route_prefix', 'cart')) }}">
    🛒 {{ $cart->items->sum('quantity') ?: 0 }}
    @if ($cart->total_cents > 0)
        <span>·</span>
        <span>{{ $cart->currency }} {{ number_format($cart->total_cents / 100, 2) }}</span>
    @endif
</a>
