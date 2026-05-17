<?php

declare(strict_types=1);

namespace Acme\Cart\Blocks;

use Acme\Cart\Models\Cart;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Header / sidebar mini-cart: item count + total. Reads the resolved
 * Cart from the container — only meaningful inside the web group where
 * CartIdentifier middleware has run.
 */
final class CartSummaryBlock extends AbstractBlock
{
    public static function key(): string { return 'cart.summary'; }

    public static function label(): string { return 'Cart · Mini summary'; }

    public static function icon(): ?string { return 'shopping-bag'; }

    public function render(array $data, RenderContext $context): string
    {
        $cart = app()->bound(Cart::class) ? app(Cart::class) : null;
        if (! $cart) {
            return '<!-- cart.summary: no resolved cart in this request -->';
        }

        return $this->view('acme-cart::blocks.summary', ['cart' => $cart], $context);
    }
}
