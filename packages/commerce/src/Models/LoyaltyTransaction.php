<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use HasUlid;

    public $timestamps = false;

    public const TYPE_EARN   = 'earn';
    public const TYPE_REDEEM = 'redeem';
    public const TYPE_EXPIRE = 'expire';
    public const TYPE_ADJUST = 'adjust';

    protected $table = 'acme_commerce_loyalty_transactions';

    protected $fillable = [
        'account_id', 'type', 'amount', 'balance_after',
        'reference_type', 'reference_id', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }
}
