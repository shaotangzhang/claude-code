<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasUlid;

    protected $table = 'acme_inventory_batches';

    protected $fillable = [
        'sku_id', 'warehouse_id', 'lot_code', 'expiry_date',
        'on_hand', 'reserved', 'received_at', 'supplier_ref',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date'   => 'date',
            'received_at'   => 'date',
            'on_hand'       => 'integer',
            'reserved'      => 'integer',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function available(): int
    {
        return max(0, $this->on_hand - $this->reserved);
    }
}
