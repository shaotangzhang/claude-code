<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key:        'rbac.roles',
        label:      'Roles & permissions',
        area:       'admin',
        route:      'acme.rbac.roles.index',
        icon:       'shield',
        capability: 'rbac.role.view',
        group:      'Identity',
        order:      30,
    ),
];
