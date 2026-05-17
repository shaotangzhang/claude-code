<?php

declare(strict_types=1);

namespace App\Models;

use Acme\Auth\Models\User as BaseUser;
use Acme\Rbac\Concerns\HasRoles;

/**
 * Host project's concrete User class. Extends acme/auth's base and
 * opts into rbac role membership via the HasRoles trait. Laravel's
 * auth config points at this class via config/auth.php.
 */
class User extends BaseUser
{
    use HasRoles;
}
