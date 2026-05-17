<?php

declare(strict_types=1);

namespace Acme\CmsCore\Blocks;

use Acme\Contracts\Cms\RenderContext;

/**
 * Trusted-HTML block. Stores raw HTML in data.html and outputs verbatim.
 * Authoring of this block type must be gated by capability cms.html.author.
 */
final class HtmlBlock extends AbstractBlock
{
    public static function key(): string { return 'cms.html'; }

    public static function label(): string { return 'HTML'; }

    public static function icon(): ?string { return 'code'; }

    public static function schema(): array
    {
        return [
            'fields' => [
                ['key' => 'html', 'type' => 'longtext', 'label' => 'HTML', 'required' => true],
            ],
        ];
    }

    public function render(array $data, RenderContext $context): string
    {
        return (string) ($data['html'] ?? '');
    }

    public function validate(array $data): array
    {
        $errors = [];
        if (! isset($data['html']) || trim((string) $data['html']) === '') {
            $errors['html'][] = 'HTML body is required.';
        }

        return $errors;
    }
}
