<?php

declare(strict_types=1);

namespace Acme\Starter\Console;

use Acme\Contracts\Module\ModuleRegistry;
use Illuminate\Console\Command;

final class ModulesCommand extends Command
{
    protected $signature   = 'acme:modules {--json}';
    protected $description = 'List installed acme/* modules with their dependencies.';

    public function handle(ModuleRegistry $registry): int
    {
        $modules = $registry->all();
        usort($modules, fn ($a, $b) => [$a->layer, $a->key] <=> [$b->layer, $b->key]);

        if ($this->option('json')) {
            $this->line(json_encode(array_map(fn ($m) => [
                'key'     => $m->key,
                'title'   => $m->title,
                'version' => $m->version,
                'layer'   => $m->layer,
                'depends' => $m->depends,
                'package' => $m->package,
            ], $modules), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Title', 'Version', 'Layer', 'Depends', 'Package'],
            array_map(fn ($m) => [
                $m->key, $m->title, $m->version, $m->layer,
                implode(', ', $m->depends), $m->package ?? '-',
            ], $modules),
        );

        return self::SUCCESS;
    }
}
