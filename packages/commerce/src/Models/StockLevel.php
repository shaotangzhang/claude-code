<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Catalog\Models\Sku;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    use HasUlid;

    protected $table = 'acme_commerce_stock_levels';

    protected $fillable = ['sku_id', 'warehouse_id', 'on_hand', 'reserved'];

    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function available(): int
    {
        return max(0, $this->on_hand - $this->reserved);
    }
}
