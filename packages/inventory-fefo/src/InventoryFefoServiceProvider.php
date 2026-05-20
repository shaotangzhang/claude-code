<?php

declare(strict_types=1);

namespace Acme\InventoryFefo;

use Acme\Contracts\Commerce\StockAllocator;
use Acme\InventoryFefo\Console\AutoDiscountCommand;
use Acme\InventoryFefo\Console\ExpiringCommand;
use Acme\InventoryFefo\Console\ReceiveCommand;
use Acme\InventoryFefo\Console\TransferCommand;
use Acme\Starter\Support\PackageServiceProvider;

final class InventoryFefoServiceProvider extends PackageServiceProvider
{
    protected string $key = 'inventory-fefo';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        // Replace commerce's default allocator. commerce's StockService
        // remains usable for receive/adjust admin paths; only the order
        // flow goes through FEFO.
        $this->app->singleton(FefoStockAllocator::class);
        $this->app->singleton(StockAllocator::class, FefoStockAllocator::class);
    }

    protected function packageBoot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ReceiveCommand::class,
                ExpiringCommand::class,
                TransferCommand::class,
                AutoDiscountCommand::class,
            ]);
        }
    }
}
