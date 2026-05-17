<section class="acme-wishlist">
    <h1>{{ $list->name }}</h1>

    @if (session('status')) <div class="alert">{{ session('status') }}</div> @endif

    @if ($list->items->isEmpty())
        <p>Your wishlist is empty.</p>
    @else
        <table>
            <thead><tr><th>Item</th><th>SKU</th><th>Note</th><th>Added</th><th></th></tr></thead>
            <tbody>
                @foreach ($list->items as $item)
                    <tr>
                        <td>{{ $item->sku->product->title ?? '' }}</td>
                        <td>{{ $item->sku->code ?? '—' }}</td>
                        <td>{{ $item->note }}</td>
                        <td>{{ $item->added_at?->diffForHumans() }}</td>
                        <td>
                            <form method="post" action="{{ route('acme.wishlist.items.to-cart', $item) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button>Move to cart</button>
                            </form>
                            <form method="post" action="{{ route('acme.wishlist.items.remove', $item) }}" style="display:inline">
                                @csrf @method('DELETE') <button>Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
