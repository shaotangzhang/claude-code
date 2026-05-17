<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasUlid;

    protected $table = 'acme_commerce_warehouses';

    protected $fillable = ['code', 'name', 'address_json', 'active'];

    protected function casts(): array
    {
        return ['address_json' => 'array', 'active' => 'bool'];
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }
}
