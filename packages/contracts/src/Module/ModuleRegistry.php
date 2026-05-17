<?php

declare(strict_types=1);

namespace Acme\Contracts\Module;

interface ModuleRegistry
{
    /** @return list<ModuleManifest> */
    public function all(): array;

    public function get(string $key): ?ModuleManifest;

    public function has(string $key): bool;
}
