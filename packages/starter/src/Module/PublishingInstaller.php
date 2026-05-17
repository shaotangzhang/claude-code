<?php

declare(strict_types=1);

namespace Acme\Starter\Module;

use Acme\Contracts\Module\Installer;
use Acme\Contracts\Module\ModuleRegistry;
use Illuminate\Contracts\Console\Kernel as Artisan;
use RuntimeException;

final class PublishingInstaller implements Installer
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly Artisan $artisan,
    ) {}

    public function install(string $moduleKey, bool $withSeed = false): void
    {
        $module = $this->registry->get($moduleKey)
            ?? throw new RuntimeException("Unknown module: {$moduleKey}");

        foreach ($module->depends as $dep) {
            if (! $this->registry->has($dep)) {
                throw new RuntimeException("Missing upstream module '{$dep}' required by '{$moduleKey}'.");
            }
        }

        $tag = "acme-{$module->key}";
        $this->artisan->call('vendor:publish', ['--tag' => "{$tag}-config", '--force' => false]);
        $this->artisan->call('vendor:publish', ['--tag' => "{$tag}-migrations", '--force' => false]);
        $this->artisan->call('migrate', ['--force' => true]);

        if ($withSeed) {
            $this->artisan->call('db:seed', ['--class' => 'Acme\\' . str_replace('-', '', ucwords($module->key, '-')) . '\\Database\\Seeders\\InstallSeeder']);
        }
    }

    public function uninstall(string $moduleKey, bool $withData = false): void
    {
        $module = $this->registry->get($moduleKey)
            ?? throw new RuntimeException("Unknown module: {$moduleKey}");

        if (! $withData) {
            return;
        }

        // Reserved for future down-migration support; intentionally a no-op
        // until each module ships idempotent down() migrations.
        unset($module);
    }
}
