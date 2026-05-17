<?php

declare(strict_types=1);

namespace Acme\Seo;

use Acme\Starter\Support\PackageServiceProvider;

final class SeoServiceProvider extends PackageServiceProvider
{
    protected string $key = 'seo';

    protected bool $hasMigrations = false;
    protected bool $hasRoutesWeb  = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }
}
