<?php

declare(strict_types=1);

namespace Acme\Contracts\Module;

interface CapabilityRegistry
{
    /** Register a single capability key with a human label and optional group. */
    public function register(string $key, string $label, ?string $group = null): void;

    /** @param array<string,string|array{label:string,group?:string}> $items key => label | [label, group] */
    public function registerMany(array $items): void;

    /** @return list<array{key:string,label:string,group:?string}> */
    public function all(): array;

    public function has(string $key): bool;
}
