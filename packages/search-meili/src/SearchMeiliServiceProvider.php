<?php

declare(strict_types=1);

namespace Acme\SearchMeili;

use Acme\Search\Drivers\Driver;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;

final class SearchMeiliServiceProvider extends PackageServiceProvider
{
    protected string $key = 'search-meili';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(MeiliClient::class, function ($app) {
            return new MeiliClient(
                http:           $app->make(Http::class),
                host:           (string) config('acme.search-meili.host', 'http://127.0.0.1:7700'),
                apiKey:         (string) config('acme.search-meili.api_key', ''),
                timeoutSeconds: (int)    config('acme.search-meili.timeout_seconds', 5),
            );
        });

        $this->app->singleton(MeiliDriver::class);

        // Rebind the search Driver — IndexBuilder + SearchController now
        // route through MeiliSearch transparently.
        $this->app->singleton(Driver::class, MeiliDriver::class);
    }
}
