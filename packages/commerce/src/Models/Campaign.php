<?php

declare(strict_types=1);

namespace Acme\Commerce\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasUlid;

    public const TYPE_BXGY            = 'bxgy';           // buy X get Y
    public const TYPE_BUNDLE          = 'bundle';
    public const TYPE_TIMED_DISCOUNT  = 'timed_discount';
    public const TYPE_FREEBIE         = 'freebie';

    protected $table = 'acme_commerce_campaigns';

    protected $fillable = [
        'key', 'name', 'type', 'rules_json',
        'starts_at', 'ends_at', 'active',
    ];

    protected function casts(): array
    {
        return [
            'rules_json' => 'array',
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
            'active'     => 'bool',
        ];
    }

    public function isLiveNow(): bool
    {
        if (! $this->active) return false;
        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) return false;
        if ($this->ends_at && $this->ends_at->lt($now))     return false;

        return true;
    }
}
