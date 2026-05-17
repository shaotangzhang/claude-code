<?php

declare(strict_types=1);

namespace Acme\Payments\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUlid;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';

    protected $table = 'acme_payments_transactions';

    protected $fillable = [
        'user_id', 'gateway', 'related_type', 'related_id',
        'amount_cents', 'currency', 'status', 'gateway_reference',
        'failure_reason', 'payload_json',
        'succeeded_at', 'failed_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'payload_json' => 'array',
            'succeeded_at' => 'datetime',
            'failed_at'    => 'datetime',
            'refunded_at'  => 'datetime',
        ];
    }
}
