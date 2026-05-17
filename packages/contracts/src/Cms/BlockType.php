<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

/**
 * Every Block kind in the system implements this contract. Packages register
 * one class per Block kind into the BlockRegistry; the renderer looks them up
 * by key() when realising a page version.
 */
interface BlockType
{
    public static function key(): string;

    public static function label(): string;

    public static function icon(): ?string;

    /** @return array<string,mixed> field schema, JSON-Schema-ish */
    public static function schema(): array;

    /** @param  array<string,mixed>  $data */
    public function render(array $data, RenderContext $context): string;

    /** @param  array<string,mixed>  $data */
    public function preview(array $data): string;

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,list<string>>  field => errors[]
     */
    public function validate(array $data): array;
}
