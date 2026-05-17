<?php

declare(strict_types=1);

namespace Acme\Search\Console;

use Acme\Search\Services\IndexBuilder;
use Illuminate\Console\Command;

final class ReindexCommand extends Command
{
    protected $signature   = 'acme:search:reindex {--locale=}';
    protected $description = 'Rebuild the search index from published catalog products.';

    public function handle(IndexBuilder $builder): int
    {
        $locale = $this->option('locale') ?: null;
        $n      = $builder->rebuildAll($locale);

        $this->info("Indexed {$n} products" . ($locale ? " (locale={$locale})" : '') . '.');

        return self::SUCCESS;
    }
}
