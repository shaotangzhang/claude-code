<?php

declare(strict_types=1);

namespace Acme\SkuBundles\Models;

use Acme\Catalog\Models\Sku;
use Acme\Support\Concerns\HasUlid;
use Acme\Support\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bundle extends Model
{
    use HasUlid, Sluggable;

    protected $table = 'acme_bundles';

    public string $slugSource = 'name';

    protected $fillable = [
        'key', 'slug', 'name', 'description',
        'price_cents', 'currency', 'locale', 'active',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'active'      => 'bool',
        ];
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Sku::class, 'acme_bundle_items', 'bundle_id', 'sku_id')
            ->withPivot('quantity');
    }
}
