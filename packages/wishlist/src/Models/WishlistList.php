<?php

declare(strict_types=1);

namespace Acme\Wishlist\Models;

use Acme\Auth\Models\User;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WishlistList extends Model
{
    use HasUlid;

    protected $table = 'acme_wishlist_lists';

    protected $fillable = ['user_id', 'name', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'bool'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'list_id')->orderByDesc('added_at');
    }
}
