<?php

declare(strict_types=1);

namespace Acme\I18n\Support;

use Acme\I18n\Models\Translation;
use Illuminate\Database\Eloquent\Model;

final class TranslationStore
{
    public function get(Model $owner, string $field, string $locale): ?string
    {
        $chain = array_unique(array_merge(
            [$locale],
            (array) config('acme.i18n.fallback_chain', ['en']),
        ));

        $rows = Translation::query()
            ->where('translatable_type', $owner::class)
            ->where('translatable_id', $owner->getKey())
            ->where('field', $field)
            ->whereIn('locale', $chain)
            ->pluck('value', 'locale');

        foreach ($chain as $loc) {
            if (isset($rows[$loc])) {
                return (string) $rows[$loc];
            }
        }

        return null;
    }

    public function set(Model $owner, string $field, string $locale, string $value): void
    {
        Translation::query()->updateOrCreate(
            [
                'translatable_type' => $owner::class,
                'translatable_id'   => $owner->getKey(),
                'field'             => $field,
                'locale'            => $locale,
            ],
            ['value' => $value],
        );
    }
}
