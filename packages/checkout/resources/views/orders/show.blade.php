@php $fmt = fn ($c) => $order->currency . ' ' . number_format(($c ?? 0) / 100, 2); @endphp
<h1>Order {{ $order->number }}</h1>
<p>Status: <strong>{{ $order->status->value }}</strong>
   @if ($order->paid_at) · paid {{ $order->paid_at->diffForHumans() }} @endif
</p>
<table>
    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
    <tbody>
        @foreach ($order->items as $i)
            <tr>
                <td>{{ $i->product_title }} <small>{{ $i->sku_code }}</small></td>
                <td>{{ $i->quantity }}</td>
                <td>{{ $fmt($i->unit_price_cents) }}</td>
                <td>{{ $fmt($i->line_total_cents) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<dl>
    <dt>Subtotal</dt><dd>{{ $fmt($order->subtotal_cents) }}</dd>
    @if ($order->discount_cents > 0)<dt>Discount</dt><dd>−{{ $fmt($order->discount_cents) }}</dd>@endif
    @if ($order->tax_cents > 0)<dt>Tax</dt><dd>{{ $fmt($order->tax_cents) }}</dd>@endif
    @if ($order->shipping_cents > 0)<dt>Shipping</dt><dd>{{ $fmt($order->shipping_cents) }}</dd>@endif
    <dt>Total</dt><dd><strong>{{ $fmt($order->total_cents) }}</strong></dd>
</dl>
@if ($order->invoice)
    <p>Invoice: <strong>{{ $order->invoice->number }}</strong> ({{ $order->invoice->status }})</p>
@endif
