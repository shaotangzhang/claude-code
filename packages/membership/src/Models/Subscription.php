<?php

declare(strict_types=1);

namespace Acme\Membership\Models;

use Acme\Auth\Models\User;
use Acme\Membership\Enums\SubscriptionStatus;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasUlid;

    protected $table = 'acme_membership_subscriptions';

    protected $fillable = [
        'user_id', 'plan_id', 'status',
        'started_at', 'current_period_start', 'current_period_end',
        'trial_ends_at', 'canceled_at', 'cancel_at_period_end',
        'paused_at', 'paused_until', 'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'status'               => SubscriptionStatus::class,
            'started_at'           => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end'   => 'datetime',
            'trial_ends_at'        => 'datetime',
            'canceled_at'          => 'datetime',
            'paused_at'            => 'datetime',
            'paused_until'         => 'datetime',
            'cancel_at_period_end' => 'bool',
            'meta_json'            => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class)->orderBy('created_at');
    }

    public function scopeForUser(Builder $q, string $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeGrantingTier(Builder $q): Builder
    {
        return $q->whereIn('status', [
            SubscriptionStatus::Trialing->value,
            SubscriptionStatus::Active->value,
            SubscriptionStatus::PastDue->value,
        ]);
    }
}
