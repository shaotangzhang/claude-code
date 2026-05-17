<?php

declare(strict_types=1);

namespace Acme\Starter\Module;

use Acme\Contracts\Module\ModuleManifest;
use Acme\Contracts\Module\ModuleRegistry;
use RuntimeException;

final class ComposerModuleRegistry implements ModuleRegistry
{
    /** @var array<string,ModuleManifest>|null */
    private ?array $cache = null;

    public function __construct(private readonly string $installedJsonPath) {}

    public function all(): array
    {
        return array_values($this->load());
    }

    public function get(string $key): ?ModuleManifest
    {
        return $this->load()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->load()[$key]);
    }

    /** @return array<string,ModuleManifest> */
    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! is_file($this->installedJsonPath)) {
            throw new RuntimeException("installed.json not found at {$this->installedJsonPath}");
        }

        $raw       = json_decode((string) file_get_contents($this->installedJsonPath), true);
        $packages  = $raw['packages'] ?? $raw ?? [];
        $manifests = [];

        foreach ($packages as $pkg) {
            $module = $pkg['extra']['acme']['module'] ?? null;
            if (! is_array($module) || empty($module['key'])) {
                continue;
            }

            $manifests[$module['key']] = new ModuleManifest(
                key: (string) $module['key'],
                title: (string) ($module['title'] ?? $module['key']),
                version: (string) ($module['version'] ?? ($pkg['version'] ?? '0.0.0')),
                layer: (int) ($module['layer'] ?? 99),
                depends: array_values(array_map('strval', (array) ($module['depends'] ?? []))),
                package: (string) ($pkg['name'] ?? null),
                extra: $module,
            );
        }

        return $this->cache = $manifests;
    }
}
