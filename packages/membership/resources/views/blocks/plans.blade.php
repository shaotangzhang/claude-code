<section class="membership-plans">
    <ul class="grid">
        @foreach ($plans as $plan)
            <li class="plan tier-{{ $plan->tier->key }}">
                <h3>{{ $plan->name }} <small>{{ $plan->tier->name }}</small></h3>
                <p class="price">
                    @php $amount = number_format($plan->price_cents / 100, 2); @endphp
                    @if ($plan->billing_period->value === 'once')
                        <strong>{{ $plan->currency }} {{ $amount }}</strong> one-time
                    @else
                        <strong>{{ $plan->currency }} {{ $amount }}</strong> / {{ $plan->billing_period->value }}
                    @endif
                </p>
                @if ($plan->trial_days > 0)
                    <p class="trial">{{ $plan->trial_days }}-day free trial</p>
                @endif
                @if ($plan->tier->perks_json)
                    <ul class="perks">
                        @foreach ($plan->tier->perks_json as $perk)
                            <li>{{ is_array($perk) ? ($perk['label'] ?? '') : $perk }}</li>
                        @endforeach
                    </ul>
                @endif
                @auth
                    <form method="post" action="{{ route('acme.membership.subscribe') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit">Subscribe</button>
                    </form>
                @else
                    <a href="{{ route('acme.auth.login') }}">Sign in to subscribe</a>
                @endauth
            </li>
        @endforeach
    </ul>
</section>
