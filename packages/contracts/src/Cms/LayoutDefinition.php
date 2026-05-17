<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

final readonly class LayoutDefinition
{
    /**
     * @param  list<SlotDefinition>  $slots
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $template,         // view name, e.g. "acme-cms-core::layouts.default"
        public array $slots = [],
        public ?string $themeKey = null, // null = layout shipped by a non-theme package
    ) {}
}
