@php $fmt = fn ($c) => $cart->currency . ' ' . number_format(($c ?? 0) / 100, 2); @endphp
<section class="checkout">
    <h1>Checkout</h1>

    @if (session('status')) <div class="alert">{{ session('status') }}</div> @endif

    @if ($cart->items->isEmpty())
        <p>Your cart is empty. <a href="{{ url(config('acme.cart.route_prefix', 'cart')) }}">Back to cart</a>.</p>
    @else
        <form method="post" action="{{ route('acme.checkout.place') }}">
            @csrf

            <fieldset>
                <legend>Shipping address</legend>
                <label>Recipient <input name="shipping[recipient]" required></label>
                <label>Country (ISO2) <input name="shipping[country]" required maxlength="2"></label>
                <label>Region <input name="shipping[region]"></label>
                <label>City <input name="shipping[city]"></label>
                <label>Postal code <input name="shipping[postal_code]"></label>
                <label>Address <input name="shipping[line1]"></label>
                <label>Phone <input name="shipping[phone]"></label>
            </fieldset>

            <fieldset>
                <legend>Shipping option</legend>
                @foreach ($shippingOptions as $opt)
                    <label>
                        <input type="radio" name="shipping_option_key" value="{{ $opt->key }}" @checked($loop->first)>
                        {{ $opt->label }} — {{ $fmt($opt->costCents) }}
                        @if ($opt->estimatedDaysMin) ({{ $opt->estimatedDaysMin }}–{{ $opt->estimatedDaysMax }} days)@endif
                    </label>
                @endforeach
            </fieldset>

            <fieldset>
                <legend>Payment</legend>
                @foreach ($gateways as $g)
                    <label><input type="radio" name="payment_gateway" value="{{ $g }}" @checked($loop->first)> {{ ucfirst($g) }}</label>
                @endforeach
            </fieldset>

            <h2>Summary</h2>
            <p>Subtotal: {{ $fmt($cart->subtotal_cents) }}</p>
            @if ($cart->discount_cents > 0) <p>Discount: −{{ $fmt($cart->discount_cents) }}</p> @endif
            @if ($cart->tax_cents > 0)      <p>Tax: {{ $fmt($cart->tax_cents) }}</p> @endif
            @if ($cart->shipping_cents > 0) <p>Shipping: {{ $fmt($cart->shipping_cents) }}</p> @endif
            <p><strong>Total: {{ $fmt($cart->total_cents) }}</strong></p>

            <button type="submit">Place order</button>
        </form>
    @endif
</section>
