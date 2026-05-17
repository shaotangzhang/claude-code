<?php

declare(strict_types=1);

namespace Acme\Catalog\Models;

use Acme\Media\Models\MediaFile;
use Acme\Support\Concerns\HasUlid;
use Acme\Support\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUlid, Sluggable, SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    protected $table = 'acme_catalog_products';

    public string $slugSource = 'title';

    protected $fillable = [
        'category_id', 'brand_id', 'slug', 'title', 'summary', 'description',
        'locale', 'status', 'attrs_json', 'meta_json',
    ];

    protected function casts(): array
    {
        return ['attrs_json' => 'array', 'meta_json' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class)->orderBy('position');
    }

    /**
     * Images attached via acme_media_attachments (polymorphic). The host
     * decides the "role" string used at upload time, conventionally
     * 'cover' for the primary image and 'gallery' for additional ones.
     */
    public function images(): MorphToMany
    {
        return $this->morphToMany(MediaFile::class, 'attachable', 'acme_media_attachments', 'attachable_id', 'file_id')
            ->withPivot(['role', 'position'])
            ->orderByPivot('position');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED);
    }

    public function priceFrom(): ?int
    {
        return $this->skus->min('price_cents');
    }

    public function priceTo(): ?int
    {
        return $this->skus->max('price_cents');
    }
}
