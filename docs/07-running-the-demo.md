# 07 · 跑起 apps/demo

`apps/demo` 是吃自己狗粮的 Laravel 12 宿主，把 42 个 `acme/*` 包全装上来。

## 0. 前置

- PHP **≥ 8.3**，扩展齐全：`pdo_mysql`（或 sqlite3）、`mbstring`、`openssl`、`tokenizer`、`xml`、`ctype`、`json`、`bcmath`、`fileinfo`
- Composer 2.x
- MySQL 8 / MariaDB 10.6+ / PostgreSQL 14+ / SQLite 3.35+

## 1. 安装

```bash
cd apps/demo
composer install
cp .env.example .env
php artisan key:generate
```

Composer 通过 `repositories` 配置把所有 `../../packages/*` symlink 进 `vendor/`。SP 自动发现走 `extra.laravel.providers`，无需手动注册。

> 如果你的目录权限或文件系统不支持 symlink（某些共享主机），改用 `--prefer-source`：composer 会复制而不是 link，迁移过程仍正确。

## 2. 数据库

编辑 `.env` 里的 `DB_*`，然后：

```bash
# 用 mysql：先在数据库里建库
mysql -u root -e "CREATE DATABASE acme_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 或用 sqlite：
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
touch database/database.sqlite

# 跑全部包的迁移（一次 ~40 张表）
php artisan migrate --force

# 种子（idempotent）
php artisan db:seed --force
```

种子写一个 super-admin（`super@acme.test` / `secret123`）+ 一篇 blog + 一个产品 + SKU + 仓库 + gold-monthly 计划 + launch-week 折扣 + US 邮区。

## 3. RBAC capability 同步

```bash
php artisan acme:rbac:sync-capabilities
```

把内存中的 capability 表落到 `acme_rbac_capabilities`，UI 上才看得到完整的权限列表。

## 4. 起服务

```bash
php artisan serve
```

## 5. 哪里点

| URL | 说明 |
| --- | --- |
| `/_welcome` | demo 自定义起页（写在 `routes/web.php`） |
| `/_modules` | 列出 42 个已装包（smoke check） |
| `/up` | Laravel 健康探针 |
| `/blog/hello-world` | 种子文章，通过 cms-core 默认 layout 渲染 |
| `/catalog/acme-tee` | 种子产品 |
| `/cart` | 空购物车；从产品页加项；自动看到"launch-week 10% off" |
| `/checkout` | 选地址 + 物流 + Manual 网关 + 下单 |
| `/admin` | 后台外壳（登录后才能看到完整导航） |
| `/account` | 用户中心 |
| `/account/membership` | 订阅状态 |
| `/account/returns` | RMA 自助门户 |
| `/account/wishlist` | 心愿单 |
| `/search?q=tee` | 搜索（默认 DatabaseDriver） |
| `/sitemap.xml` | SEO 包自动生成 |
| `/blog/feed.xml` | 博客 RSS |

## 6. 常用命令

```bash
# 模块清单 + 依赖
php artisan acme:modules                    # 全部
php artisan acme:modules --json             # JSON

# 搜索
php artisan acme:search:reindex
php artisan acme:search:reindex --locale=en

# 订阅 / 弃车 / 库存
php artisan acme:membership:tick
php artisan acme:abandoned-cart:tick --dry-run
php artisan acme:inventory-fefo:expiring --days=30
php artisan acme:inventory-fefo:auto-discount --days=14 --percent=20

# 主题脚手架
php artisan acme:theme:make boutique        # 生成新主题包
php artisan acme:cms:theme:activate boutique
```

## 7. 切换可插拔实现

每个 contract 都可以由 host 在 `AppServiceProvider::register()` 重绑：

```php
// 切到 MeiliSearch
$this->app->singleton(\Acme\Search\Drivers\Driver::class, \Acme\SearchMeili\MeiliDriver::class);

// 切到 FEFO 库存
$this->app->singleton(\Acme\Contracts\Commerce\StockAllocator::class, \Acme\InventoryFefo\FefoStockAllocator::class);

// 切到 PriceBook
$this->app->singleton(\Acme\Contracts\Commerce\PriceResolver::class, \Acme\MultiCurrencyPricing\PriceBookResolver::class);

// 切到自家 Tax/Shipping
$this->app->singleton(\Acme\Contracts\Commerce\TaxCalculator::class, \YourCo\GstCalculator::class);
```

或者更简单：在 `composer.json` 里 require 那个 sibling 包，它的 SP 会自动重绑。

## 8. 部署到 cPanel（共享主机）

如果是 cPanel 类的共享主机，建议用 cPanel 内置的 **Git Version Control** 拉仓库，然后 cPanel Terminal 跑 `composer install` + `migrate`。详见 [docs/02-conventions.md](02-conventions.md) §12.1 分支策略。

## 9. 测试 / CI

```bash
# 单个包
cd packages/<pkg> && composer install && vendor/bin/phpunit

# 全树
for d in packages/*/; do
  (cd "$d" && [ -f composer.json ] && composer install --quiet 2>/dev/null && vendor/bin/phpunit --colors=never 2>&1 | grep -E '^(OK|FAILURES|ERRORS)' )
done
```

CI 的 dependency-direction 守门（`.github/workflows/ci.yml` 里 `dep-direction` job）保证：上层包永不能 require 下层包。

## 10. 常见坑

| 现象 | 原因 / 修法 |
| --- | --- |
| `Class "Acme\Auth\Models\User" not found` | `composer dump-autoload` |
| `Foreign key constraint fails` 在迁移 | host 改了迁移顺序；保留 acme 各包默认顺序就好 |
| `Driver not found` for sqlite | `apt install php8.4-sqlite3` / 或编辑 `.env` 切回 mysql |
| `vendor:publish` 找不到资源 | 装包后跑 `php artisan package:discover --ansi` |
| `composer install` 在某 path repo 上 fail | 该包的 `version` 字段缺；`grep -L '"version"' packages/*/composer.json` 找出 |
| webhook 404 | 路由前缀按 host config 改了；用 `php artisan route:list \| grep webhook` 看实际 URL |
