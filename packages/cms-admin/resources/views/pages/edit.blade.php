@extends('acme-admin::layout')
@section('title', 'Edit page')
@section('content')
    <h1>Edit: {{ $page->title }}</h1>
    <p>Status: <strong>{{ $page->status }}</strong> · Layout: {{ $page->layout->key }} · Locale: {{ $page->locale }}</p>

    @if (! $version)
        <p>No draft yet. Click "Create draft" to start editing.</p>
    @else
        <form method="post" action="{{ route('acme.cms.admin.versions.save', $version) }}">
            @csrf @method('PUT')
            <h2>Draft #{{ Str::limit($version->id, 8, '') }} <small>· {{ $version->created_at->diffForHumans() }}</small></h2>

            @foreach ($version->blocks->groupBy('slot_key') as $slotKey => $blocks)
                <fieldset>
                    <legend>Slot: {{ $slotKey }}</legend>
                    @foreach ($blocks as $i => $b)
                        <div class="block">
                            <input type="hidden" name="blocks[{{ $i }}][slot_key]" value="{{ $b->slot_key }}">
                            <input type="hidden" name="blocks[{{ $i }}][block_type]" value="{{ $b->block_type }}">
                            <input type="hidden" name="blocks[{{ $i }}][position]" value="{{ $b->position }}">
                            <label>{{ $b->block_type }}</label>
                            <textarea name="blocks[{{ $i }}][data]" rows="6">{{ json_encode($b->data_json, JSON_PRETTY_PRINT) }}</textarea>
                        </div>
                    @endforeach
                </fieldset>
            @endforeach

            <button type="submit">Save draft</button>
        </form>

        <form method="post" action="{{ route('acme.cms.admin.pages.publish', [$page, $version]) }}">
            @csrf
            <label>Schedule (optional): <input type="datetime-local" name="publish_at"></label>
            <button type="submit">Publish this draft</button>
        </form>
    @endif

    <h3>Version history</h3>
    <ol>
        @foreach ($page->versions as $v)
            <li>
                {{ $v->created_at->diffForHumans() }} · {{ $v->note ?? '—' }}
                @if ($v->id === $page->current_version_id) <strong>(current)</strong>
                @else
                    <form method="post" action="{{ route('acme.cms.admin.pages.rollback', [$page, $v]) }}" style="display:inline">
                        @csrf <button type="submit">Restore</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ol>
@endsection
