<h1>Your orders</h1>
@if ($orders->isEmpty())
    <p>No orders yet.</p>
@else
    <table>
        <thead><tr><th>Number</th><th>Status</th><th>Total</th><th>Placed</th></tr></thead>
        <tbody>
            @foreach ($orders as $o)
                <tr>
                    <td><a href="{{ route('acme.checkout.orders.show', $o) }}">{{ $o->number }}</a></td>
                    <td>{{ $o->status->value }}</td>
                    <td>{{ $o->currency }} {{ number_format($o->total_cents / 100, 2) }}</td>
                    <td>{{ $o->placed_at?->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $orders->links() }}
@endif
