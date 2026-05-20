<section class="returns-portal">
    <h1>Start a return — order {{ $order->number }}</h1>

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('acme.returns-portal.store', ['orderId' => $order->id]) }}">
        @csrf

        <p>Tick the items you want to return and adjust quantities:</p>

        <table>
            <thead><tr><th>Return?</th><th>Item</th><th>Qty available</th><th>Qty to return</th><th>Condition</th><th>Item reason</th></tr></thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr>
                        <td><input type="checkbox" name="items[{{ $i }}][selected]" value="1"></td>
                        <td>{{ $item->product_title }} <small>{{ $item->sku_code }}</small>
                            <input type="hidden" name="items[{{ $i }}][order_item_id]" value="{{ $item->id }}"></td>
                        <td>{{ $item->quantity }}</td>
                        <td><input type="number" name="items[{{ $i }}][quantity]" min="1" max="{{ $item->quantity }}" value="1"></td>
                        <td>
                            <select name="items[{{ $i }}][condition]">
                                <option value="">—</option>
                                <option value="new">New / unopened</option>
                                <option value="opened">Opened</option>
                                <option value="damaged">Damaged</option>
                            </select>
                        </td>
                        <td><input type="text" name="items[{{ $i }}][reason]" maxlength="255"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <label>Reason (overall): <textarea name="reason" rows="3" maxlength="1000"></textarea></label>

        <button type="submit">Submit return request</button>
    </form>
</section>
