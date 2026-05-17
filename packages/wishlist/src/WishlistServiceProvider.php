<?php

declare(strict_types=1);

namespace Acme\Wishlist;

use Acme\Contracts\Cms\BlockRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Acme\Wishlist\Blocks\WishlistSummaryBlock;
use Acme\Wishlist\Services\WishlistService;

final class WishlistServiceProvider extends PackageServiceProvider
{
    protected string $key = 'wishlist';

    protected bool $hasViews        = true;
    protected bool $hasRoutesWeb    = true;
    protected bool $hasCapabilities = true;
    protected bool $hasNavigation   = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(WishlistService::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(BlockRegistry::class, function (BlockRegistry $reg): void {
            $reg->register(WishlistSummaryBlock::class);
        });
    }
}
