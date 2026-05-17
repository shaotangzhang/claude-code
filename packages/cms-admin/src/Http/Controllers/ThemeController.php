<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Http\Controllers;

use Acme\CmsAdmin\Services\ThemeActivationService;
use Acme\CmsCore\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class ThemeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('cms.theme.manage');

        $themes = Theme::query()->orderBy('name')->get();

        return new Response((string) view('acme-cms-admin::themes.index', compact('themes'))->render());
    }

    public function activate(Theme $theme, ThemeActivationService $svc): RedirectResponse
    {
        $this->authorize('cms.theme.manage');

        $svc->activate($theme);

        return back()->with('status', "Activated {$theme->name}.");
    }
}
