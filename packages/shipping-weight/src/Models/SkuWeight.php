<?php

declare(strict_types=1);

namespace Acme\ShippingWeight\Models;

use Illuminate\Database\Eloquent\Model;

class SkuWeight extends Model
{
    protected $table      = 'acme_shipping_sku_weights';
    protected $primaryKey = 'sku_id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['sku_id', 'weight_g', 'dim_w_mm', 'dim_h_mm', 'dim_d_mm'];

    protected function casts(): array
    {
        return [
            'weight_g' => 'integer',
            'dim_w_mm' => 'integer',
            'dim_h_mm' => 'integer',
            'dim_d_mm' => 'integer',
        ];
    }
}
