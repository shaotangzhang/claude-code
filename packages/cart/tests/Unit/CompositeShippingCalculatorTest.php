<?php

declare(strict_types=1);

namespace Acme\Cart\Tests\Unit;

use Acme\Cart\Shipping\CompositeShippingCalculator;
use Acme\Cart\Shipping\FlatRateShipping;
use Acme\Cart\Shipping\ShippingMethodRegistry;
use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingMethod;
use Acme\Contracts\Commerce\ShippingOption;
use Orchestra\Testbench\TestCase;

final class CompositeShippingCalculatorTest extends TestCase
{
    public function test_returns_only_flat_when_registry_empty(): void
    {
        config()->set('acme.cart.shipping.flat_rate_cents', 599);
        config()->set('acme.cart.shipping.builtin_flat_enabled', true);
        config()->set('acme.cart.shipping.free_above_cents', 0);

        $opts = (new CompositeShippingCalculator(new ShippingMethodRegistry(), new FlatRateShipping()))
            ->options(['__subtotal_cents' => 1_000], 'USD', null);

        $this->assertCount(1, $opts);
        $this->assertSame('standard', $opts[0]->key);
        $this->assertSame(599, $opts[0]->costCents);
    }

    public function test_appends_registry_methods_to_flat(): void
    {
        config()->set('acme.cart.shipping.flat_rate_cents', 0);
        config()->set('acme.cart.shipping.builtin_flat_enabled', true);

        $reg = new ShippingMethodRegistry();
        $reg->register($this->fixedMethod('express', 'Express', 1500));
        $reg->register($this->fixedMethod('overnight', 'Overnight', 2999));

        $opts = (new CompositeShippingCalculator($reg, new FlatRateShipping()))
            ->options(['__subtotal_cents' => 5_000], 'USD', null);

        $keys = array_map(fn ($o) => $o->key, $opts);
        $this->assertContains('standard', $keys);
        $this->assertContains('express', $keys);
        $this->assertContains('overnight', $keys);
        $this->assertCount(3, $opts);
    }

    public function test_disabling_flat_hides_it(): void
    {
        config()->set('acme.cart.shipping.flat_rate_cents', 599);
        config()->set('acme.cart.shipping.builtin_flat_enabled', false);

        $reg = new ShippingMethodRegistry();
        $reg->register($this->fixedMethod('express', 'Express', 1500));

        $opts = (new CompositeShippingCalculator($reg, new FlatRateShipping()))
            ->options(['__subtotal_cents' => 5_000], 'USD', null);

        $keys = array_map(fn ($o) => $o->key, $opts);
        $this->assertNotContains('standard', $keys);
        $this->assertContains('express', $keys);
    }

    private function fixedMethod(string $key, string $label, int $cents): ShippingMethod
    {
        return new class($key, $label, $cents) implements ShippingMethod {
            public function __construct(
                private readonly string $k,
                private readonly string $l,
                private readonly int $cents,
            ) {}
            public function key(): string { return $this->k; }
            public function rate(array $items, string $currency, ?Address $destination, int $subtotalCents): array
            {
                return [new ShippingOption($this->k, $this->l, $this->cents, $currency)];
            }
        };
    }
}
