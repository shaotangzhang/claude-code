<section class="returns-portal">
    <h1>Return {{ $rma->number }}</h1>
    <p>Status: <strong>{{ $rma->status }}</strong> ·
       Requested {{ $rma->requested_at?->diffForHumans() }}
       @if ($rma->refunded_at) · refunded {{ $rma->refunded_at->diffForHumans() }} @endif
    </p>

    @if ($rma->reason)<p>Reason: {{ $rma->reason }}</p>@endif

    <h2>Items</h2>
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Condition</th><th>Item reason</th></tr></thead>
        <tbody>
            @foreach ($rma->items as $line)
                <tr>
                    <td>
                        {{ $line->orderItem?->product_title }}
                        <small>{{ $line->orderItem?->sku_code }}</small>
                    </td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ $line->condition ?? '—' }}</td>
                    <td>{{ $line->reason ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><a href="{{ route('acme.returns-portal.index') }}">← Back to all returns</a></p>
</section>
