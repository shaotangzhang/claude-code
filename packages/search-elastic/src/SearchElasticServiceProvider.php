<?php

declare(strict_types=1);

namespace Acme\SearchElastic;

use Acme\Search\Drivers\Driver;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;

final class SearchElasticServiceProvider extends PackageServiceProvider
{
    protected string $key = 'search-elastic';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(ElasticClient::class, function ($app) {
            return new ElasticClient(
                http:           $app->make(Http::class),
                host:           (string) config('acme.search-elastic.host', 'http://127.0.0.1:9200'),
                apiKey:         (string) config('acme.search-elastic.api_key', ''),
                username:       (string) config('acme.search-elastic.username', ''),
                password:       (string) config('acme.search-elastic.password', ''),
                timeoutSeconds: (int)    config('acme.search-elastic.timeout_seconds', 5),
            );
        });

        $this->app->singleton(ElasticDriver::class);
        $this->app->singleton(Driver::class, ElasticDriver::class);
    }
}
