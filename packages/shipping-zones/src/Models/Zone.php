<?php

declare(strict_types=1);

namespace Acme\ShippingZones\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasUlid;

    protected $table = 'acme_shipping_zones';

    protected $fillable = ['key', 'name', 'active'];

    protected function casts(): array { return ['active' => 'bool']; }

    public function rates(): HasMany
    {
        return $this->hasMany(ZoneRate::class)->where('active', true)->orderBy('position');
    }
}
