<?php

declare(strict_types=1);

namespace Acme\Wishlist\Models;

use Acme\Catalog\Models\Sku;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_wishlist_items';

    protected $fillable = ['list_id', 'sku_id', 'note', 'added_at'];

    protected function casts(): array
    {
        return ['added_at' => 'datetime'];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(WishlistList::class, 'list_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
