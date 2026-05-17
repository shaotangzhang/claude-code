<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

final readonly class SlotDefinition
{
    /**
     * @param  list<string>|null  $allowed  Block keys allowed in this slot; null = any.
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?array $allowed = null,
        public ?int $max = null,
    ) {}
}
