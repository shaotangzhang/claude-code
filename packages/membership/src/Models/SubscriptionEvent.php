<?php

declare(strict_types=1);

namespace Acme\Membership\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_membership_subscription_events';

    protected $fillable = ['subscription_id', 'event_type', 'payload_json', 'created_at'];

    protected function casts(): array
    {
        return ['payload_json' => 'array', 'created_at' => 'datetime'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
