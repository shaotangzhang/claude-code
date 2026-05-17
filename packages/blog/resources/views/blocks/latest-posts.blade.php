<aside class="latest-posts">
    <h3>Latest posts</h3>
    <ul>
        @foreach ($posts as $p)
            <li>
                <a href="{{ url(config('acme.blog.route_prefix', 'blog') . '/' . $p->slug) }}">{{ $p->title }}</a>
                @if ($p->published_at)<small> · {{ $p->published_at->diffForHumans() }}</small>@endif
            </li>
        @endforeach
    </ul>
</aside>
