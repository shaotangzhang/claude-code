<?php

declare(strict_types=1);

namespace Acme\Cart\Models;

use Acme\Auth\Models\User;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasUlid;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_MERGED    = 'merged';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_CONVERTED = 'converted';

    protected $table = 'acme_cart_carts';

    protected $fillable = [
        'user_id', 'guest_token', 'currency', 'locale', 'status',
        'subtotal_cents', 'discount_cents', 'tax_cents', 'shipping_cents', 'total_cents', 'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents'  => 'integer',
            'discount_cents'  => 'integer',
            'tax_cents'       => 'integer',
            'shipping_cents'  => 'integer',
            'total_cents'     => 'integer',
            'meta_json'       => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'acme_cart_cart_coupons', 'cart_id', 'coupon_id')
            ->withPivot(['applied_amount_cents', 'applied_at']);
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }
}
