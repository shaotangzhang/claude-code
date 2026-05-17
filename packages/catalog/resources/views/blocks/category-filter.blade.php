<aside class="catalog-filter">
    <h3>Categories</h3>
    <ul>
        @foreach ($categories as $c)
            <li>
                <a href="?category={{ $c->slug }}">{{ $c->name }}</a>
                @if ($c->children->isNotEmpty())
                    <ul>
                        @foreach ($c->children as $cc)
                            <li><a href="?category={{ $cc->slug }}">{{ $cc->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($brands->isNotEmpty())
        <h3>Brands</h3>
        <ul>
            @foreach ($brands as $b)
                <li><a href="?brand={{ $b->slug }}">{{ $b->name }}</a></li>
            @endforeach
        </ul>
    @endif
</aside>
