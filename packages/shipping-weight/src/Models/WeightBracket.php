<?php

declare(strict_types=1);

namespace Acme\ShippingWeight\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class WeightBracket extends Model
{
    use HasUlid;

    protected $table = 'acme_shipping_weight_brackets';

    protected $fillable = [
        'key', 'label', 'min_g', 'max_g', 'cost_cents', 'currency',
        'days_min', 'days_max', 'active',
    ];

    protected function casts(): array
    {
        return [
            'min_g'      => 'integer',
            'max_g'      => 'integer',
            'cost_cents' => 'integer',
            'days_min'   => 'integer',
            'days_max'   => 'integer',
            'active'     => 'bool',
        ];
    }

    public function matches(int $totalG): bool
    {
        if ($totalG < $this->min_g) return false;
        if ($this->max_g !== null && $totalG > $this->max_g) return false;

        return true;
    }
}
