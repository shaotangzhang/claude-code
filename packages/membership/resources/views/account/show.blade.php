<h1>My membership</h1>

@if ($currentTier)
    <p>Current tier: <strong>{{ $currentTier->name }}</strong> (level {{ $currentTier->level }}).</p>
@else
    <p>No active membership.</p>
@endif

@if (session('status')) <div class="alert">{{ session('status') }}</div> @endif

@if ($subscriptions->isEmpty())
    <p><a href="{{ url(config('acme.membership.route_prefix', 'membership') . '/plans') }}">Browse plans</a></p>
@else
    <ul>
        @foreach ($subscriptions as $sub)
            <li>
                <strong>{{ $sub->plan->name }}</strong> ({{ $sub->plan->tier->name }})
                · status: <em>{{ $sub->status->value }}</em>
                @if ($sub->current_period_end) · renews {{ $sub->current_period_end->diffForHumans() }} @endif
                @if ($sub->status->grantsTier())
                    <form method="post" action="{{ route('acme.membership.cancel', $sub) }}" style="display:inline">
                        @csrf @method('DELETE') <button>Cancel</button>
                    </form>
                    <form method="post" action="{{ route('acme.membership.pause', $sub) }}" style="display:inline">
                        @csrf <button>Pause</button>
                    </form>
                @elseif ($sub->status->value === 'paused')
                    <form method="post" action="{{ route('acme.membership.resume', $sub) }}" style="display:inline">
                        @csrf <button>Resume</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@endif
