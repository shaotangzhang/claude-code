<?php

declare(strict_types=1);

namespace Acme\CmsAdmin;

use Acme\CmsAdmin\Console\ActivateThemeCommand;
use Acme\CmsAdmin\Console\MakeThemeCommand;
use Acme\Starter\Support\PackageServiceProvider;

final class CmsAdminServiceProvider extends PackageServiceProvider
{
    protected string $key = 'cms-admin';

    protected bool $hasViews         = true;
    protected bool $hasRoutesAdmin   = true;
    protected bool $hasCapabilities  = true;
    protected bool $hasNavigation    = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageBoot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeThemeCommand::class,
                ActivateThemeCommand::class,
            ]);
        }
    }
}
