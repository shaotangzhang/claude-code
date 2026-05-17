<?php

declare(strict_types=1);

namespace Acme\SkuBundles;

use Acme\Cart\Adjustments\AdjustmentRegistry;
use Acme\SkuBundles\Providers\BundleAdjustmentProvider;
use Acme\SkuBundles\Services\BundleService;
use Acme\Starter\Support\PackageServiceProvider;

final class SkuBundlesServiceProvider extends PackageServiceProvider
{
    protected string $key = 'sku-bundles';

    protected bool $hasViews        = true;
    protected bool $hasRoutesWeb    = true;
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
        $this->app->singleton(BundleService::class);
        $this->app->singleton(BundleAdjustmentProvider::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(AdjustmentRegistry::class, function (AdjustmentRegistry $r): void {
            $r->register($this->app->make(BundleAdjustmentProvider::class));
        });
    }
}
