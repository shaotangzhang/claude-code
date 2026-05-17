<?php

declare(strict_types=1);

namespace Acme\Auth\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasUlid;

    protected $table = 'acme_auth_login_log';

    public $timestamps = false;

    protected $fillable = ['user_id', 'email', 'ip', 'user_agent', 'result', 'attempted_at'];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }
}
