<section class="acme-search-results">
    <h1>Search</h1>
    <form method="get">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search…">
        <button>Go</button>
    </form>

    <p>{{ $result['total'] }} result{{ $result['total'] === 1 ? '' : 's' }}.</p>

    <aside class="facets">
        @foreach ($result['facets'] as $field => $buckets)
            @if ($buckets)
                <section>
                    <h3>{{ ucfirst($field) }}</h3>
                    <ul>
                        @foreach ($buckets as $bucket => $count)
                            <li><a href="?{{ http_build_query(array_merge(request()->query(), [$field => $bucket])) }}">{{ $bucket }} ({{ $count }})</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    </aside>

    <ul class="results">
        @foreach ($result['items'] as $row)
            <li>
                <a href="{{ url(config('acme.catalog.route_prefix', 'catalog') . '/' . ($row['product_id'] ?? '')) }}">
                    <strong>{{ $row['title'] ?? '' }}</strong>
                </a>
                @if (! empty($row['brand']))<small>· {{ $row['brand'] }}</small>@endif
                @if (! empty($row['category']))<small>· {{ $row['category'] }}</small>@endif
                @if (isset($row['min_price_cents']))
                    <span>· {{ number_format($row['min_price_cents'] / 100, 2) }}</span>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($result['total'] > $perPage)
        @php
            $pages = (int) ceil($result['total'] / $perPage);
            $base  = request()->query();
        @endphp
        <nav class="pages">
            @for ($p = 1; $p <= $pages; $p++)
                <a href="?{{ http_build_query(array_merge($base, ['page' => $p])) }}" @class(['active' => $p === $page])>{{ $p }}</a>
            @endfor
        </nav>
    @endif
</section>
