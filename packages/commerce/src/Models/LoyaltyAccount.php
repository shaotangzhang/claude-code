<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Auth\Models\User;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyAccount extends Model
{
    use HasUlid;

    protected $table = 'acme_commerce_loyalty_accounts';

    protected $fillable = ['user_id', 'balance', 'lifetime_earned'];

    protected function casts(): array
    {
        return ['balance' => 'integer', 'lifetime_earned' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'account_id');
    }
}
