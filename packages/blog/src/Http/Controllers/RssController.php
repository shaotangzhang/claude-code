<?php

declare(strict_types=1);

namespace Acme\Blog\Http\Controllers;

use Acme\Blog\Models\Article;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class RssController extends Controller
{
    public function __invoke(): Response
    {
        $ttl = (int) config('acme.blog.rss.cache_ttl_seconds', 600);
        $xml = cache()->remember('acme.blog.rss', $ttl, fn () => $this->build());

        return new Response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $limit = (int) config('acme.blog.rss.item_limit', 30);

        $articles = Article::query()
            ->with(['author'])
            ->published()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'slug', 'title', 'excerpt', 'published_at', 'author_id']);

        $title = e((string) config('acme.blog.rss.site_title', 'Blog'));
        $desc  = e((string) config('acme.blog.rss.site_description', ''));
        $link  = url(config('acme.blog.route_prefix', 'blog'));

        $items = '';
        foreach ($articles as $a) {
            $url  = url(config('acme.blog.route_prefix', 'blog') . '/' . $a->slug);
            $pub  = $a->published_at?->toRfc822String();
            $items .= "  <item>\n"
                . "    <title>" . htmlspecialchars($a->title, ENT_QUOTES | ENT_XML1) . "</title>\n"
                . "    <link>" . htmlspecialchars($url, ENT_QUOTES | ENT_XML1) . "</link>\n"
                . "    <guid isPermaLink=\"true\">" . htmlspecialchars($url, ENT_QUOTES | ENT_XML1) . "</guid>\n"
                . ($pub ? "    <pubDate>{$pub}</pubDate>\n" : '')
                . "    <description>" . htmlspecialchars((string) $a->excerpt, ENT_QUOTES | ENT_XML1) . "</description>\n"
                . "  </item>\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . "<rss version=\"2.0\"><channel>\n"
            . "  <title>{$title}</title>\n"
            . "  <link>{$link}</link>\n"
            . "  <description>{$desc}</description>\n"
            . $items
            . "</channel></rss>";
    }
}
