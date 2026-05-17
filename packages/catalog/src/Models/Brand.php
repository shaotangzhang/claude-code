<?php

declare(strict_types=1);

namespace Acme\Catalog\Models;

use Acme\Support\Concerns\HasUlid;
use Acme\Support\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasUlid, Sluggable;

    protected $table = 'acme_catalog_brands';

    public string $slugSource = 'name';

    protected $fillable = ['slug', 'name', 'description', 'locale'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
