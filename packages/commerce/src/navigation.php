<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'commerce.inventory', label: 'Inventory',
        area: 'admin', route: 'acme.commerce.admin.inventory.index',
        icon: 'box', capability: 'commerce.stock.adjust',
        group: 'Commerce', order: 10,
    ),
    new NavigationItem(
        key: 'commerce.returns', label: 'Returns',
        area: 'admin', route: 'acme.commerce.admin.returns.index',
        icon: 'rotate-ccw', capability: 'commerce.return.view',
        group: 'Commerce', order: 20,
    ),
    new NavigationItem(
        key: 'commerce.reviews', label: 'Reviews',
        area: 'admin', route: 'acme.commerce.admin.reviews.index',
        icon: 'message-circle', capability: 'commerce.review.moderate',
        group: 'Commerce', order: 30,
    ),
    new NavigationItem(
        key: 'commerce.campaigns', label: 'Campaigns',
        area: 'admin', route: 'acme.commerce.admin.campaigns.index',
        icon: 'megaphone', capability: 'commerce.campaign.manage',
        group: 'Commerce', order: 40,
    ),
    new NavigationItem(
        key: 'commerce.loyalty', label: 'Loyalty',
        area: 'admin', route: 'acme.commerce.admin.loyalty.index',
        icon: 'star', capability: 'commerce.loyalty.adjust',
        group: 'Commerce', order: 50,
    ),
];
