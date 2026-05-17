@php use Acme\Catalog\Support\Money; @endphp
<section class="catalog-grid">
    <ul class="grid">
        @foreach ($products as $p)
            <li class="card">
                @if ($p->images->isNotEmpty())
                    <img src="{{ $p->images->first()->url() }}" alt="{{ $p->title }}">
                @endif
                <h3><a href="{{ url(config('acme.catalog.route_prefix', 'catalog') . '/' . $p->slug) }}">{{ $p->title }}</a></h3>
                @if ($p->brand)<p class="brand">{{ $p->brand->name }}</p>@endif
                @if (($min = $p->priceFrom()) !== null)
                    @php $max = $p->priceTo(); $cur = $p->skus->first()?->currency ?? 'USD'; @endphp
                    <p class="price">
                        @if ($max !== null && $max !== $min)
                            {{ Money::format($min, $cur) }} – {{ Money::format($max, $cur) }}
                        @else
                            {{ Money::format($min, $cur) }}
                        @endif
                    </p>
                @endif
            </li>
        @endforeach
    </ul>
    {{ $products->links() }}
</section>
