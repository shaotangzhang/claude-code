<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Auth\Models\User;
use Acme\Catalog\Models\Product;
use Acme\Checkout\Models\Order;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasUlid;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM     = 'spam';

    protected $table = 'acme_commerce_reviews';

    protected $fillable = ['product_id', 'user_id', 'order_id', 'rating', 'title', 'body', 'status'];

    protected function casts(): array { return ['rating' => 'integer']; }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
