<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'cart.coupons', label: 'Coupons',
        area: 'admin', route: 'acme.cart.admin.coupons.index',
        icon: 'percent', capability: 'cart.coupon.manage',
        group: 'Cart', order: 10,
    ),
    new NavigationItem(
        key: 'cart.carts', label: 'Carts',
        area: 'admin', route: 'acme.cart.admin.carts.index',
        icon: 'shopping-cart', capability: 'cart.view',
        group: 'Cart', order: 20,
    ),
];
