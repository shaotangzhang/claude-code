<?php

declare(strict_types=1);

namespace Acme\Membership\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    use HasUlid;

    protected $table = 'acme_membership_tiers';

    protected $fillable = ['key', 'name', 'level', 'description', 'perks_json'];

    protected function casts(): array
    {
        return ['perks_json' => 'array', 'level' => 'integer'];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }
}
