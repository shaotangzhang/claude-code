<?php

declare(strict_types=1);

namespace Acme\Catalog\Blocks;

use Acme\Catalog\Models\Product;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Single product detail block. Resolves by id or slug.
 * data: { id?, slug?, show_gallery?, show_skus? }
 */
final class ProductBlock extends AbstractBlock
{
    public static function key(): string { return 'catalog.product'; }

    public static function label(): string { return 'Catalog · Product'; }

    public static function icon(): ?string { return 'package'; }

    public static function schema(): array
    {
        return [
            'fields' => [
                ['key' => 'id',           'type' => 'string', 'label' => 'Product ID'],
                ['key' => 'slug',         'type' => 'string', 'label' => 'or slug'],
                ['key' => 'show_gallery', 'type' => 'bool',   'label' => 'Show image gallery', 'default' => true],
                ['key' => 'show_skus',    'type' => 'bool',   'label' => 'Show SKU variants',  'default' => true],
            ],
        ];
    }

    public function render(array $data, RenderContext $context): string
    {
        $product = $this->resolve($data, $context->locale());
        if (! $product) {
            return '<!-- catalog.product: not found -->';
        }

        return $this->view('acme-catalog::blocks.product', [
            'product'      => $product,
            'show_gallery' => (bool) ($data['show_gallery'] ?? true),
            'show_skus'    => (bool) ($data['show_skus'] ?? true),
        ], $context);
    }

    public function validate(array $data): array
    {
        return empty($data['id']) && empty($data['slug'])
            ? ['id' => ['Provide either id or slug.']]
            : [];
    }

    private function resolve(array $data, string $locale): ?Product
    {
        if (! empty($data['id'])) {
            return Product::query()->with(['brand', 'category', 'skus', 'images'])->published()->find((string) $data['id']);
        }
        if (! empty($data['slug'])) {
            return Product::query()->with(['brand', 'category', 'skus', 'images'])
                ->published()->where('locale', $locale)->where('slug', (string) $data['slug'])->first();
        }

        return null;
    }
}
