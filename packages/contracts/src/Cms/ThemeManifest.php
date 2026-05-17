<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

final readonly class ThemeManifest
{
    /**
     * @param  array<string,mixed>  $tokens
     * @param  list<string>  $layouts
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $version,
        public string $viewPath,
        public string $assetPath,
        public array $tokens = [],
        public array $layouts = [],
    ) {}
}
