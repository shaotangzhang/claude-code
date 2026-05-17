<?php

declare(strict_types=1);

namespace Acme\CmsCore\Blocks;

use Acme\Contracts\Cms\BlockType;
use Acme\Contracts\Cms\RenderContext;

abstract class AbstractBlock implements BlockType
{
    public static function icon(): ?string { return null; }

    public static function schema(): array { return []; }

    public function preview(array $data): string
    {
        return $this->render($data, new \Acme\CmsCore\Rendering\RenderContext('en'));
    }

    public function validate(array $data): array
    {
        return [];
    }

    /** Convenience helper: render an array of $data through a view. */
    protected function view(string $name, array $data, RenderContext $ctx): string
    {
        return (string) view($name, $data + ['ctx' => $ctx])->render();
    }
}
