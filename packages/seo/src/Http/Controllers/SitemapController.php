<?php

declare(strict_types=1);

namespace Acme\Seo\Http\Controllers;

use Acme\CmsCore\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $ttl = (int) config('acme.seo.sitemap.cache_ttl_seconds', 600);
        $xml = cache()->remember('acme.seo.sitemap', $ttl, fn () => $this->build());

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = Page::query()
            ->published()
            ->orderBy('updated_at', 'desc')
            ->limit((int) config('acme.seo.sitemap.max_urls_per_file', 50000))
            ->get(['slug', 'locale', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $p) {
            $loc  = url(ltrim($p->slug ?: '/', '/'));
            $lm   = $p->updated_at?->toAtomString();
            $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_QUOTES | ENT_XML1) . '</loc>'
                . ($lm ? "<lastmod>{$lm}</lastmod>" : '')
                . '</url>' . "\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }
}
