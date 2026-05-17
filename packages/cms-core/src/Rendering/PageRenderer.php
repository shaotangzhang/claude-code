<?php

declare(strict_types=1);

namespace Acme\CmsCore\Rendering;

use Acme\CmsCore\Models\Page;
use Acme\Contracts\Auth\UserResolver;
use Acme\Contracts\Cms\ThemeRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use RuntimeException;

final class PageRenderer
{
    public function __construct(
        private readonly SlotRenderer $slots,
        private readonly ThemeRegistry $themes,
        private readonly ViewFactory $views,
        private readonly ?UserResolver $users = null,
    ) {}

    public function render(Page $page): string
    {
        $version = $page->currentVersion
            ?? throw new RuntimeException("Page {$page->id} has no current version.");

        $version->loadMissing('blocks');

        $ctx = new RenderContext(
            locale:   $page->locale,
            pageId:   $page->id,
            userId:   $this->users?->currentUserId(),
            themeKey: $this->themes->active()?->key,
        );

        $slots = $this->slots->renderAll($version, $ctx);

        $template = $page->layout->template;
        if (! $this->views->exists($template)) {
            throw new RuntimeException("Layout template not found: {$template}");
        }

        return (string) $this->views->make($template, [
            'page'  => $page,
            'slots' => $slots,
            'ctx'   => $ctx,
        ])->render();
    }
}
