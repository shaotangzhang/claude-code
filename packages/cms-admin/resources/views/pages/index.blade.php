@extends('acme-admin::layout')
@section('title', 'Pages')
@section('content')
    <h1>Pages</h1>
    @if (session('status')) <div class="alert">{{ session('status') }}</div> @endif
    <table>
        <thead><tr><th>Title</th><th>Slug</th><th>Locale</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
            @foreach ($pages as $p)
                <tr>
                    <td>{{ $p->title }}</td>
                    <td>{{ $p->slug }}</td>
                    <td>{{ $p->locale }}</td>
                    <td>{{ $p->status }}</td>
                    <td>{{ $p->updated_at?->diffForHumans() }}</td>
                    <td><a href="{{ route('acme.cms.admin.pages.edit', $p) }}">Edit</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $pages->links() }}
@endsection
