<?php

declare(strict_types=1);

namespace Acme\Wishlist\Blocks;

use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Auth\UserResolver;
use Acme\Contracts\Cms\RenderContext;
use Acme\Wishlist\Models\WishlistList;

/**
 * Header-style mini-summary: shows item count, hides itself for guests.
 */
final class WishlistSummaryBlock extends AbstractBlock
{
    public static function key(): string { return 'wishlist.summary'; }

    public static function label(): string { return 'Wishlist · Mini summary'; }

    public static function icon(): ?string { return 'heart'; }

    public function render(array $data, RenderContext $context): string
    {
        $userId = $context->userId();
        if (! $userId) {
            // Try the resolver too, since RenderContext may not have it populated.
            $userId = app()->bound(UserResolver::class) ? app(UserResolver::class)->currentUserId() : null;
        }
        if (! $userId) {
            return '';
        }

        $count = (int) WishlistList::query()->where('user_id', $userId)
            ->withCount('items')->get()->sum('items_count');

        return $this->view('acme-wishlist::blocks.summary', ['count' => $count], $context);
    }
}
