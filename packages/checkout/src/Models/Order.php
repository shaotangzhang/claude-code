<?php

declare(strict_types=1);

namespace Acme\Checkout\Models;

use Acme\Auth\Models\User;
use Acme\Checkout\Enums\OrderStatus;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasUlid;

    protected $table = 'acme_checkout_orders';

    protected $fillable = [
        'number', 'user_id', 'currency', 'status',
        'subtotal_cents', 'discount_cents', 'tax_cents', 'shipping_cents', 'total_cents',
        'billing_address', 'shipping_address', 'shipping_option_key',
        'payment_gateway', 'payment_transaction_id',
        'placed_at', 'paid_at', 'fulfilled_at', 'canceled_at', 'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'status'            => OrderStatus::class,
            'subtotal_cents'    => 'integer',
            'discount_cents'    => 'integer',
            'tax_cents'         => 'integer',
            'shipping_cents'    => 'integer',
            'total_cents'       => 'integer',
            'billing_address'   => 'array',
            'shipping_address'  => 'array',
            'meta_json'         => 'array',
            'placed_at'         => 'datetime',
            'paid_at'           => 'datetime',
            'fulfilled_at'      => 'datetime',
            'canceled_at'       => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function scopeForUser(Builder $q, string $userId): Builder
    {
        return $q->where('user_id', $userId);
    }
}
