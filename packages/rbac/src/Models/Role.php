<?php

declare(strict_types=1);

namespace Acme\Rbac\Models;

use Acme\Auth\Models\User;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasUlid;

    protected $table = 'acme_rbac_roles';

    protected $fillable = ['key', 'label', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'acme_rbac_role_user', 'role_id', 'user_id');
    }

    public function capabilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Capability::class,
            'acme_rbac_role_capability',
            'role_id',
            'capability_key',
            'id',
            'key',
        );
    }
}
