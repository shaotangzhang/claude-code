<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'membership.plans', label: 'Plans',
        area: 'admin', route: 'acme.membership.admin.plans.index',
        icon: 'award', capability: 'membership.plan.manage',
        group: 'Membership', order: 10,
    ),
    new NavigationItem(
        key: 'membership.subscriptions', label: 'Subscriptions',
        area: 'admin', route: 'acme.membership.admin.subscriptions.index',
        icon: 'repeat', capability: 'membership.subscription.view',
        group: 'Membership', order: 20,
    ),
    new NavigationItem(
        key: 'account.membership', label: 'Membership',
        area: 'user-center', route: 'acme.membership.show',
        order: 40,
    ),
];
