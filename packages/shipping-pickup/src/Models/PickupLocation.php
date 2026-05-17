<?php

declare(strict_types=1);

namespace Acme\ShippingPickup\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class PickupLocation extends Model
{
    use HasUlid;

    protected $table = 'acme_shipping_pickup_locations';

    protected $fillable = [
        'key', 'name', 'country', 'region', 'city', 'postal_code',
        'line1', 'phone', 'hours',
        'ready_days_min', 'ready_days_max', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active'          => 'bool',
            'ready_days_min'  => 'integer',
            'ready_days_max'  => 'integer',
        ];
    }
}
