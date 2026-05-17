<?php

declare(strict_types=1);

namespace Acme\Admin;

use Acme\Admin\Registry\InMemoryNavigationRegistry;
use Acme\Contracts\Auth\UserResolver;
use Acme\Contracts\Module\NavigationRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class AdminServiceProvider extends PackageServiceProvider
{
    protected string $key = 'admin';

    protected bool $hasMigrations  = false;
    protected bool $hasViews        = true;
    protected bool $hasRoutesAdmin  = true;
    protected bool $hasCapabilities = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(NavigationRegistry::class, function ($app) {
            return new InMemoryNavigationRegistry(
                $app->bound(UserResolver::class) ? $app->make(UserResolver::class) : null,
            );
        });
    }
}
