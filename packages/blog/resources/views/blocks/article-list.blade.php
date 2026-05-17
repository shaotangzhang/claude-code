<section class="blog-list">
    @foreach ($articles as $a)
        <article class="card">
            <h2><a href="{{ url(config('acme.blog.route_prefix', 'blog') . '/' . $a->slug) }}">{{ $a->title }}</a></h2>
            <p class="meta">
                @if ($a->published_at) <time>{{ $a->published_at->format('Y-m-d') }}</time> @endif
                @if ($a->author) · by {{ $a->author->name }} @endif
                @if ($a->category) · {{ $a->category->name }} @endif
            </p>
            @if ($a->excerpt)<p>{{ $a->excerpt }}</p>@endif
        </article>
    @endforeach
    {{ $articles->links() }}
</section>
