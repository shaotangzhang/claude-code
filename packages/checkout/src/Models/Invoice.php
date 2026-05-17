<?php

declare(strict_types=1);

namespace Acme\Checkout\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasUlid;

    public const STATUS_DRAFT  = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID   = 'paid';
    public const STATUS_VOID   = 'void';

    protected $table = 'acme_checkout_invoices';

    protected $fillable = ['order_id', 'number', 'status', 'pdf_path', 'issued_at', 'paid_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
