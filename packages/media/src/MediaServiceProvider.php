<?php

declare(strict_types=1);

namespace Acme\Media;

use Acme\Starter\Support\PackageServiceProvider;

final class MediaServiceProvider extends PackageServiceProvider
{
    protected string $key = 'media';

    protected bool $hasCapabilities = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }
}
