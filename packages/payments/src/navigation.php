<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'payments.transactions', label: 'Transactions',
        area: 'admin', route: 'acme.payments.admin.transactions.index',
        icon: 'credit-card', capability: 'payments.transaction.view',
        group: 'Payments', order: 10,
    ),
];
