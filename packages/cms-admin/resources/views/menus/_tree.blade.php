<ul>
    @foreach ($items as $item)
        <li>
            <a href="{{ $item->href() ?? '#' }}">{{ $item->label }}</a>
            @if ($item->children->isNotEmpty())
                @include('acme-cms-admin::menus._tree', ['items' => $item->children])
            @endif
        </li>
    @endforeach
</ul>
