<?php

declare(strict_types=1);

namespace Acme\Contracts\Module;

interface Installer
{
    public function install(string $moduleKey, bool $withSeed = false): void;

    public function uninstall(string $moduleKey, bool $withData = false): void;
}
