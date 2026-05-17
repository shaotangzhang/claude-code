<?php

declare(strict_types=1);

namespace Acme\UserCenter;

use Acme\Starter\Support\PackageServiceProvider;

final class UserCenterServiceProvider extends PackageServiceProvider
{
    protected string $key = 'user-center';

    protected bool $hasMigrations = false;
    protected bool $hasViews      = true;
    protected bool $hasRoutesWeb  = true;
    protected bool $hasNavigation = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }
}
