<?php

declare(strict_types=1);

namespace Acme\Cart\Models;

use Acme\Catalog\Models\Sku;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasUlid;

    protected $table = 'acme_cart_items';

    protected $fillable = [
        'cart_id', 'sku_id', 'quantity', 'unit_price_cents',
        'line_total_cents', 'currency', 'attrs_json',
        'is_gift', 'gift_source_key',
    ];

    protected function casts(): array
    {
        return [
            'attrs_json'        => 'array',
            'quantity'          => 'integer',
            'unit_price_cents'  => 'integer',
            'line_total_cents'  => 'integer',
            'is_gift'           => 'bool',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
