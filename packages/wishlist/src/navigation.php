<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key:   'account.wishlist',
        label: 'Wishlist',
        area:  'user-center',
        route: 'acme.wishlist.show',
        icon:  'heart',
        order: 60,
    ),
];
