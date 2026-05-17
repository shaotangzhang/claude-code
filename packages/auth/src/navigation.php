<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key:        'auth.users',
        label:      'Users',
        area:       'admin',
        route:      'acme.auth.users.index',
        icon:       'users',
        capability: 'auth.user.view',
        group:      'Identity',
        order:      10,
    ),
    new NavigationItem(
        key:        'auth.sessions',
        label:      'Sessions',
        area:       'admin',
        route:      'acme.auth.sessions.index',
        icon:       'monitor',
        capability: 'auth.session.view',
        group:      'Identity',
        order:      20,
    ),
];
