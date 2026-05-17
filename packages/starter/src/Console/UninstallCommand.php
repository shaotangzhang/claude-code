<?php

declare(strict_types=1);

namespace Acme\Starter\Console;

use Acme\Contracts\Module\Installer;
use Illuminate\Console\Command;

final class UninstallCommand extends Command
{
    protected $signature   = 'acme:uninstall {module} {--with-data}';
    protected $description = 'Uninstall an acme/* module. Use --with-data to drop tables.';

    public function handle(Installer $installer): int
    {
        $key = (string) $this->argument('module');

        if ($this->option('with-data') && ! $this->confirm("Drop all data for module '{$key}'?", false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $installer->uninstall($key, withData: (bool) $this->option('with-data'));
        $this->info("Module {$key} uninstalled.");

        return self::SUCCESS;
    }
}
