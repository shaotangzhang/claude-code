<?php

declare(strict_types=1);

namespace Acme\Blog\Blocks;

use Acme\Blog\Models\Article;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Sidebar-style widget block. data: { limit?: int }
 */
final class LatestPostsBlock extends AbstractBlock
{
    public static function key(): string { return 'blog.latest-posts'; }

    public static function label(): string { return 'Blog · Latest posts'; }

    public static function icon(): ?string { return 'clock'; }

    public function render(array $data, RenderContext $context): string
    {
        $limit = max(1, min(20, (int) ($data['limit'] ?? 5)));

        $posts = Article::query()
            ->published()
            ->where('locale', $context->locale())
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'slug', 'title', 'published_at']);

        return $this->view('acme-blog::blocks.latest-posts', [
            'posts' => $posts,
        ], $context);
    }
}
