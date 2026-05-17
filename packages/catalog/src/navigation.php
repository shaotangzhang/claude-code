<?php

declare(strict_types=1);

use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key: 'catalog.products', label: 'Products',
        area: 'admin', route: 'acme.catalog.admin.products.index',
        icon: 'package', capability: 'catalog.product.view',
        group: 'Catalog', order: 10,
    ),
    new NavigationItem(
        key: 'catalog.categories', label: 'Categories',
        area: 'admin', route: 'acme.catalog.admin.categories.index',
        icon: 'folder', capability: 'catalog.category.manage',
        group: 'Catalog', order: 20,
    ),
    new NavigationItem(
        key: 'catalog.brands', label: 'Brands',
        area: 'admin', route: 'acme.catalog.admin.brands.index',
        icon: 'tag', capability: 'catalog.brand.manage',
        group: 'Catalog', order: 30,
    ),
];
