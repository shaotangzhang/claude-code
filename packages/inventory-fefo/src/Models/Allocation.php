<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allocation extends Model
{
    use HasUlid;

    public $timestamps = false;

    public const STATE_RESERVED = 'reserved';
    public const STATE_SHIPPED  = 'shipped';
    public const STATE_RELEASED = 'released';

    protected $table = 'acme_inventory_allocations';

    protected $fillable = [
        'batch_id', 'reference_type', 'reference_id', 'quantity',
        'state', 'reserved_at', 'shipped_at', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'integer',
            'reserved_at' => 'datetime',
            'shipped_at'  => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
