<?php

declare(strict_types=1);

namespace Acme\Membership\Models;

use Acme\Membership\Enums\BillingPeriod;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUlid;

    protected $table = 'acme_membership_plans';

    protected $fillable = [
        'tier_id', 'key', 'name', 'billing_period',
        'price_cents', 'currency', 'trial_days', 'active', 'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'price_cents'    => 'integer',
            'trial_days'     => 'integer',
            'active'         => 'bool',
            'meta_json'      => 'array',
            'billing_period' => BillingPeriod::class,
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
