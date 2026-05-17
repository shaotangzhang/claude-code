<?php

declare(strict_types=1);

namespace Acme\ShippingZones\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneRate extends Model
{
    use HasUlid;

    protected $table = 'acme_shipping_zone_rates';

    protected $fillable = [
        'zone_id', 'key', 'label', 'cost_cents', 'currency',
        'min_subtotal_cents', 'max_subtotal_cents',
        'days_min', 'days_max', 'position', 'active',
    ];

    protected function casts(): array
    {
        return [
            'cost_cents' => 'integer', 'position' => 'integer',
            'active'     => 'bool',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function appliesTo(int $subtotalCents): bool
    {
        if ($this->min_subtotal_cents !== null && $subtotalCents < (int) $this->min_subtotal_cents) {
            return false;
        }
        if ($this->max_subtotal_cents !== null && $subtotalCents > (int) $this->max_subtotal_cents) {
            return false;
        }

        return true;
    }
}
