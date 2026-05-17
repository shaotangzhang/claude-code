<!DOCTYPE html>
<html lang="{{ $page->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
    {!! $slots['head'] ?? '' !!}
</head>
<body class="acme-cms">
    <header>{!! $slots['header'] ?? '' !!}</header>
    <main>{!! $slots['main'] ?? '' !!}</main>
    <aside>{!! $slots['sidebar'] ?? '' !!}</aside>
    <footer>{!! $slots['footer'] ?? '' !!}</footer>
</body>
</html>
