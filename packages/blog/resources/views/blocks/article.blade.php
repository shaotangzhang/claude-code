<article class="blog-article">
    <header>
        <h1>{{ $article->title }}</h1>
        @if ($show_meta)
            <p class="meta">
                @if ($article->author)<span>By {{ $article->author->name }}</span>@endif
                @if ($article->published_at)<time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('Y-m-d') }}</time>@endif
                @if ($article->category)<a href="{{ url(config('acme.blog.route_prefix', 'blog') . '/category/' . $article->category->slug) }}">{{ $article->category->name }}</a>@endif
            </p>
        @endif
    </header>
    @if ($article->excerpt)<p class="lead">{{ $article->excerpt }}</p>@endif
    <div class="body">{!! nl2br(e($article->body)) !!}</div>
    @if ($show_tags && $article->tags->isNotEmpty())
        <footer class="tags">
            @foreach ($article->tags as $t)
                <a href="{{ url(config('acme.blog.route_prefix', 'blog') . '/tag/' . $t->slug) }}">#{{ $t->name }}</a>
            @endforeach
        </footer>
    @endif
</article>
