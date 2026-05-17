@extends('acme-admin::layout')
@section('title', 'Menus')
@section('content')
    <h1>Menus</h1>
    @foreach ($menus as $menu)
        <section>
            <h2>{{ $menu->label }} <small>({{ $menu->key }} · {{ $menu->locale }})</small></h2>
            <p>This list view is read-only in M3. JS-backed reordering UI lands in a follow-up minor.</p>
            @include('acme-cms-admin::menus._tree', ['items' => $menu->items->whereNull('parent_id')])
        </section>
    @endforeach
@endsection
