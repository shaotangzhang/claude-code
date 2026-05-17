<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Console;

use Acme\CmsAdmin\Services\ThemeActivationService;
use Acme\CmsCore\Models\Theme;
use Illuminate\Console\Command;

final class ActivateThemeCommand extends Command
{
    protected $signature   = 'acme:cms:theme:activate {key}';
    protected $description = 'Activate a theme by key (updates the DB; emits ThemeActivated).';

    public function handle(ThemeActivationService $svc): int
    {
        $key   = (string) $this->argument('key');
        $theme = Theme::query()->where('key', $key)->first();
        if (! $theme) {
            $this->error("Theme not found: {$key}");

            return self::FAILURE;
        }
        $svc->activate($theme);
        $this->info("Activated theme: {$key}");

        return self::SUCCESS;
    }
}
