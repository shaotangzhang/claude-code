<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Point Laravel auth at our concrete User class (extends
        // Acme\Auth\Models\User and uses Acme\Rbac\Concerns\HasRoles).
        config(['auth.providers.users.model' => User::class]);
    }
}
