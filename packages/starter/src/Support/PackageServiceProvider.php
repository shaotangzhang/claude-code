<?php

declare(strict_types=1);

namespace Acme\Starter\Support;

use Illuminate\Support\ServiceProvider;

/**
 * Base ServiceProvider for every acme/* package.
 *
 * Subclasses set the protected fields and get standard publish/load wired up.
 */
abstract class PackageServiceProvider extends ServiceProvider
{
    /** Package key (kebab), e.g. "cms-core". */
    protected string $key;

    /** Absolute path to the package root. */
    protected string $root;

    protected bool $hasConfig       = true;
    protected bool $hasMigrations   = true;
    protected bool $hasViews        = false;
    protected bool $hasLang         = false;
    protected bool $hasRoutesWeb    = false;
    protected bool $hasRoutesAdmin  = false;
    protected bool $hasRoutesApi    = false;
    protected bool $hasCapabilities = false;
    protected bool $hasNavigation   = false;

    public function register(): void
    {
        if ($this->hasConfig) {
            $this->mergeConfigFrom("{$this->root}/config/{$this->key}.php", "acme.{$this->key}");
        }

        $this->packageRegister();
    }

    public function boot(): void
    {
        $viewNs = "acme-{$this->key}";

        if ($this->hasMigrations) {
            $this->loadMigrationsFrom("{$this->root}/database/migrations");
        }
        if ($this->hasViews) {
            $this->loadViewsFrom("{$this->root}/resources/views", $viewNs);
        }
        if ($this->hasLang) {
            $this->loadTranslationsFrom("{$this->root}/resources/lang", $viewNs);
        }
        if ($this->hasRoutesWeb) {
            $this->loadRoutesFrom("{$this->root}/routes/web.php");
        }
        if ($this->hasRoutesAdmin) {
            $this->loadRoutesFrom("{$this->root}/routes/admin.php");
        }
        if ($this->hasRoutesApi) {
            $this->loadRoutesFrom("{$this->root}/routes/api.php");
        }

        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
        }

        $this->registerCapabilities();
        $this->registerNavigation();

        $this->packageBoot();
    }

    protected function registerCapabilities(): void
    {
        if (! $this->hasCapabilities) {
            return;
        }
        $file = "{$this->root}/src/capabilities.php";
        if (! is_file($file)) {
            return;
        }
        $contract = \Acme\Contracts\Module\CapabilityRegistry::class;
        if (! $this->app->bound($contract)) {
            return; // rbac not installed yet; harmless no-op
        }
        $this->app->make($contract)->registerMany(require $file);
    }

    protected function registerNavigation(): void
    {
        if (! $this->hasNavigation) {
            return;
        }
        $file = "{$this->root}/src/navigation.php";
        if (! is_file($file)) {
            return;
        }
        $contract = \Acme\Contracts\Module\NavigationRegistry::class;
        if (! $this->app->bound($contract)) {
            return; // admin not installed yet
        }
        $this->app->make($contract)->registerMany(require $file);
    }

    protected function registerPublishables(): void
    {
        if ($this->hasConfig) {
            $this->publishes(
                [ "{$this->root}/config/{$this->key}.php" => config_path("acme/{$this->key}.php") ],
                "acme-{$this->key}-config"
            );
        }
        if ($this->hasViews) {
            $this->publishes(
                [ "{$this->root}/resources/views" => resource_path("views/vendor/acme-{$this->key}") ],
                "acme-{$this->key}-views"
            );
        }
        if ($this->hasMigrations) {
            $this->publishes(
                [ "{$this->root}/database/migrations" => database_path('migrations') ],
                "acme-{$this->key}-migrations"
            );
        }
    }

    /** Hook for subclasses; default no-op. */
    protected function packageRegister(): void {}

    /** Hook for subclasses; default no-op. */
    protected function packageBoot(): void {}
}
