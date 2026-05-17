@php use Acme\Catalog\Support\Money; @endphp
<article class="catalog-product">
    <header>
        <h1>{{ $product->title }}</h1>
        @if ($product->brand)<p class="brand">{{ $product->brand->name }}</p>@endif
    </header>

    @if ($show_gallery && $product->images->isNotEmpty())
        <div class="gallery">
            @foreach ($product->images as $img)
                <img src="{{ $img->url() }}" alt="{{ $img->alt ?? $product->title }}">
            @endforeach
        </div>
    @endif

    @if ($product->summary)<p class="lead">{{ $product->summary }}</p>@endif
    @if ($product->description)<div class="description">{!! nl2br(e($product->description)) !!}</div>@endif

    @if ($show_skus && $product->skus->isNotEmpty())
        <table class="skus">
            <thead><tr><th>SKU</th><th>Variant</th><th>Price</th><th>Availability</th></tr></thead>
            <tbody>
                @foreach ($product->skus as $sku)
                    <tr>
                        <td>{{ $sku->code }}</td>
                        <td>
                            @if ($sku->attrs_json)
                                @foreach ($sku->attrs_json as $k => $v)
                                    <span>{{ $k }}: {{ $v }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if ($sku->isOnSale())
                                <del>{{ Money::format($sku->list_price_cents, $sku->currency) }}</del>
                            @endif
                            <strong>{{ Money::format($sku->price_cents, $sku->currency) }}</strong>
                        </td>
                        <td>{{ $sku->stock_label ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</article>
