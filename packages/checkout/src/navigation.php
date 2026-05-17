<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'orders.index', label: 'Orders',
        area: 'admin', route: 'acme.checkout.admin.orders.index',
        icon: 'package', capability: 'order.view',
        group: 'Orders', order: 10,
    ),
    new NavigationItem(
        key: 'invoices.index', label: 'Invoices',
        area: 'admin', route: 'acme.checkout.admin.invoices.index',
        icon: 'file', capability: 'order.manage',
        group: 'Orders', order: 20,
    ),
    new NavigationItem(
        key: 'account.orders', label: 'My orders',
        area: 'user-center', route: 'acme.checkout.orders.index',
        order: 50,
    ),
];
