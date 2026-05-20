<?php

declare(strict_types=1);

namespace Acme\ReturnsPortal;

use Acme\Starter\Support\PackageServiceProvider;

final class ReturnsPortalServiceProvider extends PackageServiceProvider
{
    protected string $key = 'returns-portal';

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
