<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeThemeCommand extends Command
{
    protected $signature   = 'acme:theme:make {name : kebab-case theme key} {--path=themes : root path relative to base_path}';
    protected $description = 'Scaffold a new acme theme package directory from stubs.';

    public function handle(): int
    {
        $key  = Str::kebab((string) $this->argument('name'));
        $base = base_path(rtrim((string) $this->option('path'), '/') . '/' . $key);

        if (File::exists($base)) {
            $this->error("Directory already exists: {$base}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists("{$base}/views/layouts");
        File::ensureDirectoryExists("{$base}/public");

        $stub = __DIR__ . '/../../stubs/theme';
        $vars = [
            '{{ key }}'        => $key,
            '{{ studly }}'     => Str::studly($key),
            '{{ name }}'       => Str::headline($key),
            '{{ year }}'       => date('Y'),
        ];

        foreach ([
            'composer.json'                  => 'composer.json',
            'theme.json'                     => 'theme.json',
            'ServiceProvider.php.stub'       => 'ThemeServiceProvider.php',
            'views/layouts/default.blade.php.stub' => 'views/layouts/default.blade.php',
        ] as $stubPath => $destPath) {
            $content = (string) File::get("{$stub}/{$stubPath}");
            File::put("{$base}/{$destPath}", strtr($content, $vars));
        }

        $this->info("Theme scaffolded at: {$base}");
        $this->line("Next steps:");
        $this->line("  1. composer require {$key}/theme  (after publishing)");
        $this->line("  2. php artisan acme:cms:theme:activate {$key}");

        return self::SUCCESS;
    }
}
