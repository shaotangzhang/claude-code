<?php

declare(strict_types=1);

namespace Acme\Cart\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasUlid;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed';

    protected $table = 'acme_cart_coupons';

    protected $fillable = [
        'code', 'type', 'value', 'currency', 'min_subtotal_cents',
        'max_uses', 'used_count', 'starts_at', 'ends_at', 'active', 'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'value'              => 'integer',
            'min_subtotal_cents' => 'integer',
            'max_uses'           => 'integer',
            'used_count'         => 'integer',
            'starts_at'          => 'datetime',
            'ends_at'            => 'datetime',
            'active'             => 'bool',
            'meta_json'          => 'array',
        ];
    }

    public function isUsableNow(): bool
    {
        if (! $this->active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
