<?php

declare(strict_types=1);

namespace Acme\Blog\Blocks;

use Acme\Blog\Models\Article;
use Acme\Blog\Models\Category;
use Acme\Blog\Models\Tag;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Renders a paginated list of published articles, optionally filtered.
 * data shape: { category_slug?, tag_slug?, per_page?, order?: 'recent'|'popular' }
 */
final class ArticleListBlock extends AbstractBlock
{
    public static function key(): string { return 'blog.article-list'; }

    public static function label(): string { return 'Blog · Article list'; }

    public static function icon(): ?string { return 'list'; }

    public function render(array $data, RenderContext $context): string
    {
        $perPage = max(1, min(50, (int) ($data['per_page'] ?? config('acme.blog.list_per_page', 12))));
        $q = Article::query()->with(['author', 'category'])->published()->where('locale', $context->locale());

        if (! empty($data['category_slug'])) {
            $cat = Category::query()->where('locale', $context->locale())->where('slug', $data['category_slug'])->first();
            if ($cat) {
                $q->where('category_id', $cat->id);
            }
        }
        if (! empty($data['tag_slug'])) {
            $tag = Tag::query()->where('locale', $context->locale())->where('slug', $data['tag_slug'])->first();
            if ($tag) {
                $q->whereHas('tags', fn ($t) => $t->where('tags.id', $tag->id));
            }
        }

        $q = match ($data['order'] ?? 'recent') {
            'popular' => $q->orderByDesc('view_count'),
            default   => $q->orderByDesc('published_at'),
        };

        $articles = $q->paginate($perPage);

        return $this->view('acme-blog::blocks.article-list', [
            'articles' => $articles,
        ], $context);
    }
}
