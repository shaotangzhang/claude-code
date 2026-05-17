<?php

declare(strict_types=1);

namespace Acme\CmsCore;

use Acme\CmsCore\Blocks\HtmlBlock;
use Acme\CmsCore\Blocks\TextBlock;
use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use Acme\CmsCore\Registry\InMemoryLayoutRegistry;
use Acme\CmsCore\Registry\InMemoryThemeRegistry;
use Acme\CmsCore\Registry\InMemoryWidgetRegistry;
use Acme\CmsCore\Rendering\PageRenderer;
use Acme\CmsCore\Rendering\SlotRenderer;
use Acme\Contracts\Auth\UserResolver;
use Acme\Contracts\Cms\BlockRegistry;
use Acme\Contracts\Cms\LayoutDefinition;
use Acme\Contracts\Cms\LayoutRegistry;
use Acme\Contracts\Cms\SlotDefinition;
use Acme\Contracts\Cms\ThemeRegistry;
use Acme\Contracts\Cms\WidgetRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\View\FileViewFinder;

final class CmsCoreServiceProvider extends PackageServiceProvider
{
    protected string $key = 'cms-core';

    protected bool $hasViews         = true;
    protected bool $hasRoutesWeb     = true;
    protected bool $hasRoutesAdmin   = true;
    protected bool $hasCapabilities  = true;
    protected bool $hasNavigation    = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(BlockRegistry::class,  InMemoryBlockRegistry::class);
        $this->app->singleton(LayoutRegistry::class, InMemoryLayoutRegistry::class);
        $this->app->singleton(ThemeRegistry::class,  InMemoryThemeRegistry::class);
        $this->app->singleton(WidgetRegistry::class, InMemoryWidgetRegistry::class);

        $this->app->singleton(SlotRenderer::class, fn ($a) => new SlotRenderer($a->make(BlockRegistry::class)));

        $this->app->singleton(PageRenderer::class, fn ($a) => new PageRenderer(
            $a->make(SlotRenderer::class),
            $a->make(ThemeRegistry::class),
            $a->make(ViewFactory::class),
            $a->bound(UserResolver::class) ? $a->make(UserResolver::class) : null,
        ));
    }

    protected function packageBoot(): void
    {
        $blocks = $this->app->make(BlockRegistry::class);
        $blocks->register(TextBlock::class);
        $blocks->register(HtmlBlock::class);

        $layouts = $this->app->make(LayoutRegistry::class);
        $layouts->register(new LayoutDefinition(
            key:      'default',
            name:     'Default',
            template: 'acme-cms-core::layouts.default',
            slots:    [
                new SlotDefinition('head',    'Head'),
                new SlotDefinition('header',  'Header'),
                new SlotDefinition('main',    'Main',   max: null),
                new SlotDefinition('sidebar', 'Sidebar'),
                new SlotDefinition('footer',  'Footer'),
            ],
        ));

        $this->prependActiveThemeViewPath();
    }

    /**
     * Theme override is implemented by prepending the active theme's view path
     * into the global FileViewFinder. Theme is discovered from the DB at boot;
     * absence is harmless.
     */
    private function prependActiveThemeViewPath(): void
    {
        $finder = $this->app['view.finder'] ?? null;
        if (! $finder instanceof FileViewFinder) {
            return;
        }

        $forced = config('acme.cms-core.theme.force_active');
        $themePath = null;

        if ($forced) {
            $themePath = base_path("themes/{$forced}/views");
        } else {
            try {
                $active = \Acme\CmsCore\Models\Theme::query()->where('active', true)->first();
                if ($active) {
                    $themePath = base_path("themes/{$active->key}/views");
                }
            } catch (\Throwable) {
                // table may not yet be migrated; ignore.
            }
        }

        if ($themePath && is_dir($themePath)) {
            $finder->prependLocation($themePath);
        }
    }
}
