<?php

declare(strict_types=1);

namespace Acme\Starter\Console;

use Acme\Contracts\Module\Installer;
use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature   = 'acme:install {module} {--seed}';
    protected $description = 'Publish + migrate an acme/* module by its key.';

    public function handle(Installer $installer): int
    {
        $key = (string) $this->argument('module');
        $this->info("Installing module: {$key}");
        $installer->install($key, withSeed: (bool) $this->option('seed'));
        $this->info("Module {$key} installed.");

        return self::SUCCESS;
    }
}
