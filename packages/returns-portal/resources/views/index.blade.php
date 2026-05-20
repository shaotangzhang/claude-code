<section class="returns-portal">
    <h1>My returns</h1>
    @if (session('status')) <div class="alert">{{ session('status') }}</div> @endif

    @if ($rmas->isEmpty())
        <p>No returns yet.</p>
    @else
        <table>
            <thead><tr><th>Number</th><th>Order</th><th>Status</th><th>Items</th><th>Requested</th></tr></thead>
            <tbody>
                @foreach ($rmas as $r)
                    <tr>
                        <td><a href="{{ route('acme.returns-portal.show', $r) }}">{{ $r->number }}</a></td>
                        <td>{{ $r->order_id }}</td>
                        <td>{{ $r->status }}</td>
                        <td>{{ $r->items->sum('quantity') }}</td>
                        <td>{{ $r->requested_at?->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $rmas->links() }}
    @endif
</section>
