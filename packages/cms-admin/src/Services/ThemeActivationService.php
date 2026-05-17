<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Services;

use Acme\CmsAdmin\Events\ThemeActivated;
use Acme\CmsCore\Models\Theme;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class ThemeActivationService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly UserResolver $users,
    ) {}

    public function activate(Theme $theme): void
    {
        $previous = Theme::query()->where('active', true)->first();
        if ($previous?->id === $theme->id) {
            return;
        }

        DB::transaction(function () use ($theme, $previous): void {
            if ($previous) {
                $previous->active = false;
                $previous->save();
            }
            $theme->active = true;
            $theme->save();
        });

        $this->events->dispatch(new ThemeActivated(
            themeKey:         $theme->key,
            previousThemeKey: $previous?->key,
            authorId:         $this->users->currentUserId(),
        ));
    }
}
