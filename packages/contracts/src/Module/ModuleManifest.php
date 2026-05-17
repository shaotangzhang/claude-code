<?php

declare(strict_types=1);

namespace Acme\Contracts\Module;

final readonly class ModuleManifest
{
    /**
     * @param  list<string>  $depends
     * @param  array<string,mixed>  $extra
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $version,
        public int $layer,
        public array $depends = [],
        public ?string $package = null,
        public array $extra = [],
    ) {}
}
