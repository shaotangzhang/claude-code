@extends('acme-admin::layout')
@section('title', 'Themes')
@section('content')
    <h1>Themes</h1>
    @if (session('status')) <div class="alert">{{ session('status') }}</div> @endif
    <ul>
        @foreach ($themes as $t)
            <li>
                <strong>{{ $t->name }}</strong> v{{ $t->version }}
                @if ($t->active) <em>(active)</em>
                @else
                    <form method="post" action="{{ route('acme.cms.admin.themes.activate', $t) }}" style="display:inline">
                        @csrf <button type="submit">Activate</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@endsection
