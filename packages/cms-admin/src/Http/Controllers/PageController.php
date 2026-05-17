<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Http\Controllers;

use Acme\CmsAdmin\Services\PageDraftService;
use Acme\CmsAdmin\Services\PagePublishService;
use Acme\CmsAdmin\Services\PageRollbackService;
use Acme\CmsAdmin\Services\SlotEditor;
use Acme\CmsCore\Models\Page;
use Acme\CmsCore\Models\PageVersion;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class PageController extends Controller
{
    public function index(): Response
    {
        $this->authorize('cms.page.view');

        $pages = Page::query()->latest()->paginate(25);

        return new Response((string) view('acme-cms-admin::pages.index', compact('pages'))->render());
    }

    public function edit(Page $page, PageDraftService $draft): Response
    {
        $this->authorize('cms.page.update');

        $version = $page->versions()->where('id', '!=', $page->current_version_id)->latest('created_at')->first();
        if (! $version && config('acme.cms-admin.auto_draft_on_edit')) {
            $version = $draft->createFrom($page);
        }
        $version?->loadMissing('blocks');

        return new Response((string) view('acme-cms-admin::pages.edit', compact('page', 'version'))->render());
    }

    public function saveDraft(Request $request, PageVersion $version, SlotEditor $editor): RedirectResponse
    {
        $this->authorize('cms.page.update');

        $blocks = (array) $request->input('blocks', []);
        $editor->replace($version, $blocks);

        return back()->with('status', 'Draft saved.');
    }

    public function publish(Request $request, Page $page, PageVersion $version, PagePublishService $svc): RedirectResponse
    {
        $this->authorize('cms.page.publish');

        $at = $request->filled('publish_at') ? Carbon::parse((string) $request->input('publish_at')) : null;
        $svc->publish($page, $version, $at);

        return redirect()->route('acme.cms.admin.pages.index')->with('status', 'Published.');
    }

    public function rollback(Page $page, PageVersion $version, PageRollbackService $svc): RedirectResponse
    {
        $this->authorize('cms.page.publish');

        $svc->restore($page, $version);

        return redirect()->route('acme.cms.admin.pages.index')->with('status', 'Rolled back.');
    }
}
