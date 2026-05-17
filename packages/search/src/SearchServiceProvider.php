<?php

declare(strict_types=1);

namespace Acme\Search;

use Acme\Contracts\Cms\BlockRegistry;
use Acme\Search\Blocks\SearchBoxBlock;
use Acme\Search\Console\ReindexCommand;
use Acme\Search\Drivers\DatabaseDriver;
use Acme\Search\Drivers\Driver;
use Acme\Search\Services\IndexBuilder;
use Acme\Starter\Support\PackageServiceProvider;

final class SearchServiceProvider extends PackageServiceProvider
{
    protected string $key = 'search';

    protected bool $hasViews     = true;
    protected bool $hasRoutesWeb = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        // Default driver = database. Bind Driver to a different impl in
        // your host SP to swap out (e.g. acme/search-meili in the future).
        $this->app->singleton(Driver::class, function ($app) {
            return match ((string) config('acme.search.driver', 'database')) {
                default => $app->make(DatabaseDriver::class),
            };
        });

        $this->app->singleton(IndexBuilder::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(BlockRegistry::class, function (BlockRegistry $r): void {
            $r->register(SearchBoxBlock::class);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ReindexCommand::class]);
        }
    }
}
