@extends('acme-admin::layout')
@section('title', 'Dashboard')
@section('content')
    <h1>Dashboard</h1>
    <p>Welcome to {{ $brand }}.</p>
    <p>This shell aggregates {{ count($navigation) }} navigation entr{{ count($navigation) === 1 ? 'y' : 'ies' }} from installed modules.</p>
@endsection
