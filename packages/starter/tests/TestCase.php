<?php

declare(strict_types=1);

namespace Acme\Starter\Tests;

use Acme\Starter\StarterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [StarterServiceProvider::class];
    }
}
