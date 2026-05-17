<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $brand ?? 'Admin')</title>
</head>
<body class="acme-admin">
    <header>
        <strong>{{ $brand ?? config('acme.admin.brand', 'Acme') }}</strong>
        @auth · {{ auth()->user()->name }} @endauth
    </header>
    <nav>
        @foreach (collect($navigation ?? [])->groupBy('group') as $group => $items)
            <section>
                <h4>{{ $group ?: 'General' }}</h4>
                <ul>
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $item->route ? route($item->route) : $item->url }}">{{ $item->label }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </nav>
    <main>@yield('content')</main>
</body>
</html>
