<?php

declare(strict_types=1);

namespace Acme\Rbac\Console;

use Acme\Contracts\Module\CapabilityRegistry;
use Acme\Rbac\Models\Capability;
use Illuminate\Console\Command;

final class SyncCapabilitiesCommand extends Command
{
    protected $signature   = 'acme:rbac:sync-capabilities';
    protected $description = 'Sync in-memory capability registry to the acme_rbac_capabilities table.';

    public function handle(CapabilityRegistry $registry): int
    {
        $count = 0;
        foreach ($registry->all() as $cap) {
            Capability::query()->updateOrCreate(
                ['key' => $cap['key']],
                ['label' => $cap['label'], 'group' => $cap['group']],
            );
            $count++;
        }
        $this->info("Synced {$count} capabilities.");

        return self::SUCCESS;
    }
}
