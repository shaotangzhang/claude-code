<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key:   'account.returns',
        label: 'Returns',
        area:  'user-center',
        route: 'acme.returns-portal.index',
        icon:  'rotate-ccw',
        order: 70,
    ),
];
