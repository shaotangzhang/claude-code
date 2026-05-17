<?php

declare(strict_types=1);

namespace Acme\Auth;

use Acme\Auth\Support\AuthUserResolver;
use Acme\Contracts\Auth\UserResolver;
use Acme\Starter\Support\PackageServiceProvider;

final class AuthServiceProvider extends PackageServiceProvider
{
    protected string $key = 'auth';

    protected bool $hasViews         = true;
    protected bool $hasRoutesWeb     = true;
    protected bool $hasRoutesAdmin   = true;
    protected bool $hasCapabilities  = true;
    protected bool $hasNavigation    = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(UserResolver::class, AuthUserResolver::class);
    }
}
