<?php

declare(strict_types=1);

namespace Acme\Rbac;

use Acme\Contracts\Module\CapabilityRegistry;
use Acme\Rbac\Console\SyncCapabilitiesCommand;
use Acme\Rbac\Gates\CapabilityGate;
use Acme\Rbac\Registry\InMemoryCapabilityRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;

final class RbacServiceProvider extends PackageServiceProvider
{
    protected string $key = 'rbac';

    protected bool $hasRoutesAdmin  = true;
    protected bool $hasCapabilities = true;
    protected bool $hasNavigation   = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(CapabilityRegistry::class, InMemoryCapabilityRegistry::class);
    }

    protected function packageBoot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SyncCapabilitiesCommand::class]);
        }

        // After every package has had a chance to register capabilities in its
        // own boot(), wire each capability key to a Gate.
        $this->app->booted(function (): void {
            (new CapabilityGate(
                $this->app->make(CapabilityRegistry::class),
                $this->app->make(Gate::class),
            ))->install();
        });
    }
}
