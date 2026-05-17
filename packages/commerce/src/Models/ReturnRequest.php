<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Auth\Models\User;
use Acme\Checkout\Models\Order;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends Model
{
    use HasUlid;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_RECEIVED  = 'received';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUS_REJECTED  = 'rejected';

    protected $table = 'acme_commerce_returns';

    protected $fillable = [
        'number', 'order_id', 'user_id', 'status', 'reason',
        'refund_amount_cents', 'requested_at', 'approved_at', 'received_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount_cents' => 'integer',
            'requested_at' => 'datetime',
            'approved_at'  => 'datetime',
            'received_at'  => 'datetime',
            'refunded_at'  => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
