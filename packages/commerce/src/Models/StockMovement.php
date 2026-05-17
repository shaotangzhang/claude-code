<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasUlid;

    public $timestamps = false;

    public const TYPE_INBOUND      = 'inbound';
    public const TYPE_OUTBOUND     = 'outbound';
    public const TYPE_RESERVE      = 'reserve';
    public const TYPE_RELEASE      = 'release';
    public const TYPE_ADJUSTMENT   = 'adjustment';
    public const TYPE_TRANSFER_IN  = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';

    protected $table = 'acme_commerce_stock_movements';

    protected $fillable = [
        'sku_id', 'warehouse_id', 'type', 'quantity',
        'reference_type', 'reference_id', 'reason', 'actor_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'occurred_at' => 'datetime'];
    }
}
