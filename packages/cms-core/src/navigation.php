<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key:        'cms.pages',
        label:      'Pages',
        area:       'admin',
        route:      'acme.cms.admin.pages.index',
        icon:       'file-text',
        capability: 'cms.page.view',
        group:      'Content',
        order:      10,
    ),
    new NavigationItem(
        key:        'cms.themes',
        label:      'Themes',
        area:       'admin',
        route:      'acme.cms.admin.themes.index',
        icon:       'palette',
        capability: 'cms.theme.manage',
        group:      'Content',
        order:      90,
    ),
];
