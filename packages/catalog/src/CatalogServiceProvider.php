<?php

declare(strict_types=1);

namespace Acme\Catalog;

use Acme\Catalog\Blocks\CategoryFilterBlock;
use Acme\Catalog\Blocks\FeaturedProductsBlock;
use Acme\Catalog\Blocks\ProductBlock;
use Acme\Catalog\Blocks\ProductGridBlock;
use Acme\Contracts\Cms\BlockRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class CatalogServiceProvider extends PackageServiceProvider
{
    protected string $key = 'catalog';

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

    protected function packageBoot(): void
    {
        $this->app->resolving(BlockRegistry::class, function (BlockRegistry $reg): void {
            $reg->register(ProductBlock::class);
            $reg->register(ProductGridBlock::class);
            $reg->register(CategoryFilterBlock::class);
            $reg->register(FeaturedProductsBlock::class);
        });
    }
}
