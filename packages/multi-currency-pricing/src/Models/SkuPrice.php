<?php

declare(strict_types=1);

namespace Acme\MultiCurrencyPricing\Models;

use Acme\Catalog\Models\Sku;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuPrice extends Model
{
    use HasUlid;

    protected $table = 'acme_pricing_sku_prices';

    protected $fillable = ['sku_id', 'currency', 'price_cents', 'list_price_cents', 'active'];

    protected function casts(): array
    {
        return [
            'price_cents'      => 'integer',
            'list_price_cents' => 'integer',
            'active'           => 'bool',
        ];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
