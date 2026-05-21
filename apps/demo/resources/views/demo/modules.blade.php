<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Acme · Installed modules</title>
    <style>
        body { font: 14px/1.5 system-ui, sans-serif; max-width: 980px; margin: 2rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .35rem .6rem; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f7f7f7; font-weight: 600; }
        .l0 { color: #888; } .l1 { color: #2563eb; } .l2 { color: #059669; } .l3 { color: #c026d3; }
        code { font: 12px/1 ui-monospace, monospace; color: #555; }
    </style>
</head>
<body>
    <h1>Acme platform · {{ $modules->count() }} modules</h1>
    <p>This page is a smoke check that every <code>acme/*</code> package has been picked up by composer's Laravel auto-discovery and the module registry.</p>
    <table>
        <thead><tr><th>Layer</th><th>Key</th><th>Title</th><th>v</th><th>Depends</th></tr></thead>
        <tbody>
            @foreach ($modules as $m)
                <tr>
                    <td class="l{{ $m->layer }}">{{ $m->layer }}</td>
                    <td><code>{{ $m->key }}</code></td>
                    <td>{{ $m->title }}</td>
                    <td>{{ $m->version }}</td>
                    <td><code>{{ implode(', ', $m->depends) }}</code></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top:2rem;color:#888">
        <a href="{{ url('/up') }}">/up</a> · <a href="{{ url('/_welcome') }}">/_welcome</a> ·
        run <code>php artisan acme:modules</code> for CLI equivalent
    </p>
</body>
</html>
