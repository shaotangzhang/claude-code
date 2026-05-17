# acme/rbac

> Role-based access control. **每个 acme/* 包都通过 capability 与之协作**。

## 依赖
- [acme/auth](../auth)

## 核心机制

1. **Capability 注册中心**（`Acme\Contracts\Module\CapabilityRegistry`）：每个包在 `src/capabilities.php` 声明键 + 标签 + 分组。`PackageServiceProvider` 在 `boot()` 里把它注册进来。
2. **Role ↔ Capability ↔ User**：角色是一组 capability 的聚合，用户挂角色。
3. **Gate 自动绑定**：`acme/rbac` 在 `app booted` 钩子里，把每个已注册的 capability 键 **一一绑定为 Laravel Gate**。业务代码继续写 `@can('blog.article.publish')`，无需任何额外注册。
4. **Super role**：可配置一个超级角色（默认 `super-admin`），通过 `Gate::before` 全过。

## 表
- `acme_rbac_capabilities` — capability 元数据（可由 `php artisan acme:rbac:sync-capabilities` 同步）
- `acme_rbac_roles`
- `acme_rbac_role_capability`
- `acme_rbac_role_user`

## 用法

在宿主项目把 `HasRoles` trait 装到具体 User 上：
```php
class User extends \Acme\Auth\Models\User
{
    use \Acme\Rbac\Concerns\HasRoles;
}
```

之后控制器 / 视图：
```php
if ($request->user()->can('blog.article.publish')) { ... }
```
或 Blade：
```blade
@can('blog.article.publish') ... @endcan
```

## 命令
- `acme:rbac:sync-capabilities` — 把内存中的 capability 注册结果同步到 DB（用于报表与 UI 列表）。
