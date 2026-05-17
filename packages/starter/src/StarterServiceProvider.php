<?php

declare(strict_types=1);

namespace Acme\Starter;

use Acme\Contracts\Module\Installer;
use Acme\Contracts\Module\ModuleRegistry;
use Acme\Starter\Console\InstallCommand;
use Acme\Starter\Console\ModulesCommand;
use Acme\Starter\Console\UninstallCommand;
use Acme\Starter\Module\ComposerModuleRegistry;
use Acme\Starter\Module\PublishingInstaller;
use Acme\Starter\Support\PackageServiceProvider;

final class StarterServiceProvider extends PackageServiceProvider
{
    protected string $key = 'starter';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(ModuleRegistry::class, function () {
            $path = base_path('vendor/composer/installed.json');

            return new ComposerModuleRegistry($path);
        });

        $this->app->singleton(Installer::class, fn ($app) => new PublishingInstaller(
            $app->make(ModuleRegistry::class),
            $app->make(\Illuminate\Contracts\Console\Kernel::class),
        ));
    }

    protected function packageBoot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModulesCommand::class,
                InstallCommand::class,
                UninstallCommand::class,
            ]);
        }
    }
}
