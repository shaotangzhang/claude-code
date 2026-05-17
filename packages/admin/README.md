# acme/admin

> 管理后台外壳。**只有外壳** —— 导航聚合、布局、Dashboard。所有业务页面由各业务包注册 NavigationItem 后挂进来。

## 依赖
- [acme/rbac](../rbac)

## 核心机制

`Acme\Contracts\Module\NavigationRegistry`：每个包在 `src/navigation.php` 返回 `NavigationItem[]`。`PackageServiceProvider` 在 boot 期把它注册到 admin 的聚合器。Dashboard 视图读取所有已注册项，按 group + order 排序，根据 capability 过滤。

## 扩展点

业务包想在后台菜单里加一项：
```php
// packages/blog/src/navigation.php
use Acme\Contracts\Module\NavigationItem;

return [
    new NavigationItem(
        key:        'blog.articles',
        label:      'Articles',
        area:       'admin',
        route:      'acme.blog.articles.index',
        capability: 'blog.article.view',
        group:      'Content',
        order:      10,
    ),
];
```
并在 BlogServiceProvider 里设 `protected bool $hasNavigation = true;`。

## 视图覆盖
- Theme 包可以覆盖 `acme-admin::layout` / `acme-admin::dashboard`。
- 或 `php artisan vendor:publish --tag=acme-admin-views` 复制到 `resources/views/vendor/acme-admin/`。
