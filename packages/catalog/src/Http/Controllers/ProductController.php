<?php

declare(strict_types=1);

namespace Acme\Catalog\Http\Controllers;

use Acme\Catalog\Blocks\ProductBlock;
use Acme\Catalog\Models\Product;
use Acme\CmsCore\Rendering\RenderContext;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductController extends Controller
{
    public function show(string $slug, ProductBlock $block, ViewFactory $views): Response
    {
        $locale  = app()->getLocale();
        $product = Product::query()->published()->where('locale', $locale)->where('slug', $slug)->first();

        if (! $product) {
            throw new NotFoundHttpException("Product not found: {$slug}");
        }

        $ctx  = new RenderContext($locale);
        $html = $block->render(['id' => $product->id], $ctx);

        $body = (string) $views->make('acme-cms-core::layouts.default', [
            'page'  => (object) ['locale' => $locale, 'title' => $product->title],
            'slots' => ['main' => $html],
            'ctx'   => $ctx,
        ])->render();

        return new Response($body);
    }
}
