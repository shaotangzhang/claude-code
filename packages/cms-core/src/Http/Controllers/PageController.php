<?php

declare(strict_types=1);

namespace Acme\CmsCore\Http\Controllers;

use Acme\CmsCore\Models\Page;
use Acme\CmsCore\Rendering\PageRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PageController extends Controller
{
    public function show(Request $request, PageRenderer $renderer, string $slug = ''): Response
    {
        // When mounted as a fallback route, no {slug} param is bound — derive
        // it from the request path instead.
        if ($slug === '') {
            $slug = ltrim($request->path(), '/');
        }
        $slug   = $slug === '' || $slug === '/' ? '/' : '/' . trim($slug, '/');
        $locale = app()->getLocale();

        $page = Page::query()
            ->with(['layout', 'currentVersion.blocks'])
            ->published()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            throw new NotFoundHttpException("No published page at {$slug}");
        }

        $ttl = (int) config('acme.cms-core.cache.page_ttl', 0);
        if ($ttl <= 0) {
            return new Response($renderer->render($page));
        }

        $cacheKey = "acme.cms.page:{$page->id}:{$page->current_version_id}:{$locale}";
        $html     = cache()->remember($cacheKey, $ttl, fn () => $renderer->render($page));

        return new Response($html);
    }
}
