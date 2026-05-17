<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

interface WidgetRegistry
{
    /** @param  class-string  $widgetClass  any invokable returning string|view */
    public function register(string $key, string $widgetClass): void;

    /** @return class-string */
    public function resolve(string $key): string;

    public function has(string $key): bool;

    /** @return array<string,class-string> */
    public function all(): array;
}
