<?php

declare(strict_types=1);

namespace Acme\Catalog\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sku extends Model
{
    use HasUlid;

    protected $table = 'acme_catalog_skus';

    protected $fillable = [
        'product_id', 'code', 'price_cents', 'list_price_cents',
        'currency', 'attrs_json', 'stock_label', 'position',
    ];

    protected function casts(): array
    {
        return [
            'attrs_json'      => 'array',
            'price_cents'      => 'integer',
            'list_price_cents' => 'integer',
            'position'         => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isOnSale(): bool
    {
        return $this->list_price_cents !== null && $this->list_price_cents > $this->price_cents;
    }
}
