<?php

declare(strict_types=1);

namespace Acme\Blog\Blocks;

use Acme\Blog\Models\Article;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Renders one article. Resolves it by id (preferred) or by slug.
 * data shape: { id?: ulid, slug?: string, show_meta?: bool, show_tags?: bool }
 */
final class ArticleBlock extends AbstractBlock
{
    public static function key(): string { return 'blog.article'; }

    public static function label(): string { return 'Blog · Article'; }

    public static function icon(): ?string { return 'file-text'; }

    public static function schema(): array
    {
        return [
            'fields' => [
                ['key' => 'id',        'type' => 'string', 'label' => 'Article ID'],
                ['key' => 'slug',      'type' => 'string', 'label' => 'or slug'],
                ['key' => 'show_meta', 'type' => 'bool',   'label' => 'Show byline / date', 'default' => true],
                ['key' => 'show_tags', 'type' => 'bool',   'label' => 'Show tags',           'default' => true],
            ],
        ];
    }

    public function render(array $data, RenderContext $context): string
    {
        $article = $this->resolve($data, $context->locale());
        if (! $article) {
            return '<!-- blog.article: not found -->';
        }

        return $this->view('acme-blog::blocks.article', [
            'article'   => $article,
            'show_meta' => (bool) ($data['show_meta'] ?? true),
            'show_tags' => (bool) ($data['show_tags'] ?? true),
        ], $context);
    }

    public function validate(array $data): array
    {
        $errors = [];
        if (empty($data['id']) && empty($data['slug'])) {
            $errors['id'][] = 'Provide either id or slug.';
        }

        return $errors;
    }

    private function resolve(array $data, string $locale): ?Article
    {
        if (! empty($data['id'])) {
            return Article::query()->published()->find((string) $data['id']);
        }
        if (! empty($data['slug'])) {
            return Article::query()->published()->where('locale', $locale)->where('slug', (string) $data['slug'])->first();
        }

        return null;
    }
}
