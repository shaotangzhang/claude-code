<?php

declare(strict_types=1);

namespace Acme\Support\Concerns;

trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $value = $this->getAttribute($attribute);

        if (! is_array($value)) {
            return $value;
        }

        return $value[$locale]
            ?? $value[config('app.fallback_locale')]
            ?? array_values($value)[0]
            ?? null;
    }

    public function setTranslation(string $attribute, string $locale, mixed $value): static
    {
        $current = $this->getAttribute($attribute);
        $current = is_array($current) ? $current : [];
        $current[$locale] = $value;
        $this->setAttribute($attribute, $current);

        return $this;
    }
}
