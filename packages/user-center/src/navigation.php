<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem('account.profile',  'Profile',  'user-center', route: 'acme.account.profile',  order: 10),
    new NavigationItem('account.security', 'Security', 'user-center', route: 'acme.account.security', order: 20),
    new NavigationItem('account.sessions', 'Devices',  'user-center', route: 'acme.account.sessions', order: 30),
];
