<?php

declare(strict_types=1);

namespace Acme\Auth\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasUlid, Notifiable, SoftDeletes;

    protected $table = 'acme_auth_users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'password'                  => 'hashed',
            'two_factor_recovery_codes' => 'array',
        ];
    }
}
