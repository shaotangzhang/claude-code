<?php

declare(strict_types=1);

namespace Acme\ShippingLocalDelivery\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalZone extends Model
{
    use HasUlid;

    protected $table = 'acme_shipping_local_zones';

    protected $fillable = ['key', 'name', 'country', 'postal_prefixes_json', 'active'];

    protected function casts(): array
    {
        return ['postal_prefixes_json' => 'array', 'active' => 'bool'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(LocalRate::class, 'zone_id')->where('active', true)->orderBy('position');
    }

    /** Does the given postal code match any configured prefix? */
    public function matchesPostal(?string $postal): bool
    {
        if (! $postal) {
            return false;
        }
        $p = strtoupper(preg_replace('/\s+/', '', $postal));
        foreach ((array) $this->postal_prefixes_json as $prefix) {
            if ($p !== '' && str_starts_with($p, strtoupper((string) $prefix))) {
                return true;
            }
        }

        return false;
    }
}
