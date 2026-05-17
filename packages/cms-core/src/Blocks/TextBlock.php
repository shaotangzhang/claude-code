<?php

declare(strict_types=1);

namespace Acme\CmsCore\Blocks;

use Acme\Contracts\Cms\RenderContext;

/**
 * Plain-text block. Escapes content; preserves paragraph breaks.
 * Safe default — no authoring capability required beyond cms.page.update.
 */
final class TextBlock extends AbstractBlock
{
    public static function key(): string { return 'cms.text'; }

    public static function label(): string { return 'Text'; }

    public static function icon(): ?string { return 'type'; }

    public static function schema(): array
    {
        return [
            'fields' => [
                ['key' => 'body', 'type' => 'text', 'label' => 'Body', 'required' => true],
            ],
        ];
    }

    public function render(array $data, RenderContext $context): string
    {
        $body = (string) ($data['body'] ?? '');
        $paragraphs = preg_split('/\n{2,}/', $body) ?: [];

        return implode('', array_map(
            fn (string $p): string => '<p>' . nl2br(e(trim($p))) . '</p>',
            array_filter($paragraphs, fn ($p) => trim($p) !== ''),
        ));
    }
}
