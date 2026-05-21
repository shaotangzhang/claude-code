<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Acme · Demo</title>
    <style>
        body { font: 15px/1.6 system-ui, sans-serif; max-width: 760px; margin: 3rem auto; padding: 0 1rem; }
        code { background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font: 12px/1 ui-monospace, monospace; }
        h1 { margin-bottom: .2em; } h1 + p { color: #6b7280; margin-top: 0; }
        ul.smoke li { margin: .4em 0; }
    </style>
</head>
<body>
    <h1>Acme demo</h1>
    <p>Dogfood host for the full <code>acme/*</code> stack.</p>

    <h2>Try these</h2>
    <ul class="smoke">
        <li><a href="{{ url('/_modules') }}">/_modules</a> — list installed packages</li>
        <li><a href="{{ url('/up') }}">/up</a> — Laravel health probe</li>
        <li><a href="{{ url('/blog/hello-world') }}">/blog/hello-world</a> — seeded article (after <code>db:seed</code>)</li>
        <li><a href="{{ url('/catalog/acme-tee') }}">/catalog/acme-tee</a> — seeded product</li>
        <li><a href="{{ url('/cart') }}">/cart</a> — empty cart</li>
        <li><a href="{{ url('/membership') }}">/membership</a> — user subscriptions (requires login)</li>
        <li><a href="{{ url('/admin') }}">/admin</a> — back-office (login as <code>super@acme.test</code>)</li>
        <li><a href="{{ url('/search?q=tee') }}">/search?q=tee</a> — DatabaseDriver search</li>
        <li><a href="{{ url('/sitemap.xml') }}">/sitemap.xml</a> — auto-generated</li>
        <li><a href="{{ url('/blog/feed.xml') }}">/blog/feed.xml</a> — blog RSS</li>
    </ul>

    <h2>CLI smoke</h2>
    <pre><code>php artisan acme:modules            # full module catalog
php artisan acme:search:reindex     # populate search
php artisan acme:membership:tick    # advance subscription state machines
php artisan acme:abandoned-cart:tick --dry-run</code></pre>

    <p style="color:#9ca3af;margin-top:3rem">See <code>apps/demo/README.md</code> + <code>docs/</code> for the full story.</p>
</body>
</html>
