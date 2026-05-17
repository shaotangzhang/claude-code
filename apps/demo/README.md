# apps/demo — Dogfood Host

Thin Laravel 12 app used to verify every milestone end-to-end.

## Bootstrap (后续 M0 完成后)

```bash
composer create-project laravel/laravel apps/demo
cd apps/demo
composer config repositories.platform path "../../packages/*"
composer require acme/starter
php artisan acme:modules
```

每个里程碑结束时：
1. `composer require acme/<new-package>`
2. `php artisan acme:install <new-package>`
3. 跑 `php artisan acme:modules` 应见到新模块
4. 访问 demo 站点验证场景

> 这个目录暂时只占位。`apps/demo` 不签入完整 Laravel 应用 —— 由 CI 在测试时按需生成，避免与 upstream Laravel 升级冲突。
