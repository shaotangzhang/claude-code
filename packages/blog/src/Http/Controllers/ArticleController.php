<?php

declare(strict_types=1);

namespace Acme\Blog\Http\Controllers;

use Acme\Blog\Blocks\ArticleBlock;
use Acme\Blog\Models\Article;
use Acme\CmsCore\Rendering\RenderContext;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Article detail uses the CMS default layout but does NOT require a CMS
 * Page record per article. We render the ArticleBlock straight into the
 * "main" slot; theme override of the layout still applies.
 */
final class ArticleController extends Controller
{
    public function show(string $slug, ArticleBlock $block, ViewFactory $views): Response
    {
        $locale  = app()->getLocale();

        $article = Article::query()
            ->with(['author', 'category', 'tags'])
            ->published()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            throw new NotFoundHttpException("Article not found: {$slug}");
        }

        Article::query()->whereKey($article->id)->increment('view_count');

        $ctx  = new RenderContext($locale, pageId: null);
        $html = $block->render(['id' => $article->id, 'show_meta' => true, 'show_tags' => true], $ctx);

        $body = (string) $views->make('acme-cms-core::layouts.default', [
            'page'  => (object) ['locale' => $locale, 'title' => $article->title],
            'slots' => ['main' => $html],
            'ctx'   => $ctx,
        ])->render();

        return new Response($body);
    }
}
