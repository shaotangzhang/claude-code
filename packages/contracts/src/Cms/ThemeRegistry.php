<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

interface ThemeRegistry
{
    public function register(ThemeManifest $theme): void;

    public function resolve(string $key): ThemeManifest;

    public function has(string $key): bool;

    /** @return list<ThemeManifest> */
    public function all(): array;

    public function active(): ?ThemeManifest;

    public function activate(string $key): void;
}
