<?php

declare(strict_types=1);

namespace Acme\Checkout\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_checkout_order_items';

    protected $fillable = [
        'order_id', 'sku_id', 'sku_code', 'product_title',
        'quantity', 'unit_price_cents', 'line_total_cents', 'currency', 'attrs_json',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'integer',
            'unit_price_cents' => 'integer',
            'line_total_cents' => 'integer',
            'attrs_json'       => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
