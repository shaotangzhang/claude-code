<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

/**
 * Immutable rendering context passed to every Block::render() call.
 * Concrete value object lives in acme/cms-core; this is the shape contract.
 */
interface RenderContext
{
    public function locale(): string;

    public function pageId(): ?string;

    public function userId(): ?string;

    public function themeKey(): ?string;

    /** Free-form bag for cross-block coordination (e.g. SEO meta accumulation). */
    public function bag(): array;
}
