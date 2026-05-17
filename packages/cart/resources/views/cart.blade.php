@php $currency = $cart->currency; $fmt = fn ($c) => $currency . ' ' . number_format(($c ?? 0) / 100, 2); @endphp
<section class="acme-cart">
    <h1>Your cart</h1>

    @if (session('status')) <div class="alert">{{ session('status') }}</div> @endif

    @if ($cart->items->isEmpty())
        <p>Your cart is empty.</p>
    @else
        <table>
            <thead><tr><th>Item</th><th>Unit</th><th>Qty</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @foreach ($cart->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->sku->product->title ?? '' }}</strong>
                            <small>{{ $item->sku->code }}</small>
                        </td>
                        <td>{{ $fmt($item->unit_price_cents) }}</td>
                        <td>
                            <form method="post" action="{{ route('acme.cart.items.update', $item) }}">
                                @csrf @method('PUT')
                                <input type="number" min="0" name="quantity" value="{{ $item->quantity }}">
                                <button>Update</button>
                            </form>
                        </td>
                        <td>{{ $fmt($item->line_total_cents) }}</td>
                        <td>
                            <form method="post" action="{{ route('acme.cart.items.remove', $item) }}">
                                @csrf @method('DELETE') <button>Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <p>Subtotal: <strong>{{ $fmt($cart->subtotal_cents) }}</strong></p>
            @if ($cart->discount_cents > 0)<p>Discount: −{{ $fmt($cart->discount_cents) }}</p>@endif
            @if ($cart->tax_cents > 0)<p>Tax: {{ $fmt($cart->tax_cents) }}</p>@endif
            @if ($cart->shipping_cents > 0)<p>Shipping: {{ $fmt($cart->shipping_cents) }}</p>@endif
            <p>Total: <strong>{{ $fmt($cart->total_cents) }}</strong></p>
        </div>

        <form method="post" action="{{ route('acme.cart.coupons.apply') }}">
            @csrf
            <label>Coupon code: <input name="code" required></label>
            <button>Apply</button>
            @error('code') <small style="color:red">{{ $message }}</small> @enderror
        </form>

        @foreach ($cart->coupons as $coupon)
            <p>Coupon: <strong>{{ $coupon->code }}</strong>
                <form method="post" action="{{ route('acme.cart.coupons.remove', $coupon) }}" style="display:inline">
                    @csrf @method('DELETE') <button>Remove</button>
                </form>
            </p>
        @endforeach
    @endif
</section>
