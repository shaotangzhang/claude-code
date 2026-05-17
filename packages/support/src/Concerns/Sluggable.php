<?php

declare(strict_types=1);

namespace Acme\Support\Concerns;

use Illuminate\Support\Str;

trait Sluggable
{
    public static function bootSluggable(): void
    {
        static::saving(function ($model): void {
            $sourceAttr = property_exists($model, 'slugSource') ? $model->slugSource : 'title';
            $targetAttr = property_exists($model, 'slugAttribute') ? $model->slugAttribute : 'slug';

            if (empty($model->{$targetAttr}) && ! empty($model->{$sourceAttr})) {
                $model->{$targetAttr} = Str::slug((string) $model->{$sourceAttr});
            }
        });
    }
}
