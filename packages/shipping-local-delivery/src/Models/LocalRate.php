<?php

declare(strict_types=1);

namespace Acme\ShippingLocalDelivery\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalRate extends Model
{
    use HasUlid;

    protected $table = 'acme_shipping_local_rates';

    protected $fillable = [
        'zone_id', 'key', 'label', 'cost_cents', 'currency',
        'min_subtotal_cents', 'eta_minutes_min', 'eta_minutes_max',
        'position', 'active',
    ];

    protected function casts(): array
    {
        return [
            'cost_cents'      => 'integer',
            'position'        => 'integer',
            'eta_minutes_min' => 'integer',
            'eta_minutes_max' => 'integer',
            'active'          => 'bool',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(LocalZone::class);
    }

    public function appliesTo(int $subtotalCents): bool
    {
        if ($this->min_subtotal_cents !== null && $subtotalCents < (int) $this->min_subtotal_cents) {
            return false;
        }

        return true;
    }
}
