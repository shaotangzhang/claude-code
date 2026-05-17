<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Checkout\Models\OrderItem;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_commerce_return_items';

    protected $fillable = ['return_id', 'order_item_id', 'quantity', 'condition', 'reason'];

    protected function casts(): array { return ['quantity' => 'integer']; }

    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
