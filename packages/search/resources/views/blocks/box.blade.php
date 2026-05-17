<form class="acme-search-box" method="get" action="{{ url(config('acme.search.route_prefix', 'search')) }}">
    <input type="search" name="q" placeholder="{{ $placeholder }}" value="{{ request()->query('q') }}">
    <button type="submit">🔍</button>
</form>
