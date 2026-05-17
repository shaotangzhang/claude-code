@php use Acme\Catalog\Support\Money; @endphp
<section class="catalog-featured">
    <ul>
        @foreach ($products as $p)
            <li>
                @if ($p->images->isNotEmpty())<img src="{{ $p->images->first()->url() }}" alt="{{ $p->title }}">@endif
                <h4><a href="{{ url(config('acme.catalog.route_prefix', 'catalog') . '/' . $p->slug) }}">{{ $p->title }}</a></h4>
                @if (($min = $p->priceFrom()) !== null)
                    <p>{{ Money::format($min, $p->skus->first()?->currency ?? 'USD') }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
