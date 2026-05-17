<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'bundles.admin', label: 'Bundles',
        area: 'admin', route: 'acme.bundles.admin.index',
        icon: 'package', capability: 'bundle.manage',
        group: 'Catalog', order: 40,
    ),
];
