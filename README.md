# Acme — Laravel 12 模块化电商平台

42 个 Composer 包构成的电商 / CMS 平台。每个能力域都是独立包；项目通过 `composer require` 装配；本仓库 `apps/demo` 是吃自己狗粮的演示宿主。

```
contracts ── starter ── auth ── rbac ── admin / user-center
                                    │
                                    ▼
                         cms-core ── cms-admin
                                    │
                                    ▼
        blog · catalog · membership · cart · payments · checkout · commerce
                                    │
                                    ▼
      支付网关 (stripe / paypal / alipay / wechatpay / stripe-subs)
      物流方法 (zones / weight / free / pickup / local-delivery)
      搜索后端 (database / meili / elastic)
      通知通道 (mail / log / sms / webhook)
      库存策略 (FIFO 默认 / FEFO 替换)
      增值能力 (wishlist · loyalty-redemption · multi-currency-pricing
                · abandoned-cart · sku-bundles · returns-portal)
```

## 核心原则

- **一切皆包**：每个能力域都是独立 Composer 包，自带 ServiceProvider、迁移、配置、视图、测试
- **单向依赖**：依赖关系是 DAG；CI 守门一旦上层依赖下层即 fail
- **约定优于配置**：表前缀 `acme_<pkg>_`、视图命名空间 `acme-<pkg>::`、capability 键 `<pkg>.<resource>.<action>`，全部从一套约定推导
- **抽象 + 二实现验证**：每个跨包接口（`PaymentGateway` / `ShippingMethod` / `Channel` / `Driver` / `StockAllocator` / `CartAdjustmentProvider` / `CartGiftProvider` / `PriceResolver`）都至少有两个性质不同的实现，保证抽象扛得住扩展

## 文档索引

| 文档 | 内容 |
| --- | --- |
| [docs/01-architecture.md](docs/01-architecture.md) | 包分层、依赖图、命名空间约定 |
| [docs/02-conventions.md](docs/02-conventions.md) | 包开发规范：迁移 / 配置 / 事件 / 能力 / 视图 / 资源 / 分支策略 |
| [docs/03-roadmap.md](docs/03-roadmap.md) | M0–M18 已交付里程碑 + M10-M13 后端层（CRM/ERP/Finance/LMS）状态 |
| [docs/04-cms-rendering.md](docs/04-cms-rendering.md) | Theme → Layout → Slot → Block + Widget/Component 渲染模型 |
| [docs/05-extension-model.md](docs/05-extension-model.md) | 下游项目 / 客户化包如何扩展上游包 |
| [docs/06-package-catalog.md](docs/06-package-catalog.md) | 全 42 包速查表 + 一句话用途 + 依赖边 |
| [docs/07-running-the-demo.md](docs/07-running-the-demo.md) | 本机启动 `apps/demo`：composer / migrate / seed / serve |

## 快速启动 demo

```bash
cd apps/demo
composer install                  # symlink 全部 acme/* + 装 Laravel
cp .env.example .env
php artisan key:generate
php artisan migrate --force        # 跑 38+ 个包的迁移
php artisan db:seed --force        # 种子：1 用户 / 1 文章 / 1 产品 / 1 计划 / ...
php artisan acme:modules           # 列出 42 个已装模块
php artisan serve

open http://localhost:8000/_welcome
```

`/_modules` 列出所有已装包；`/admin` 用 `super@acme.test` / `secret123` 登录。

## 包索引（按层）

### Layer 0 — Foundation
`contracts` · `support` · `starter`

### Layer 1 — Identity
`auth` · `rbac` · `admin` · `user-center`

### Layer 2 — Content
`cms-core` · `cms-admin` · `media` · `i18n` · `seo`

### Layer 3 — Business
**核心域**：`blog` · `catalog` · `membership` · `cart` · `payments` · `checkout` · `commerce`
**支付**：`payments-stripe` · `payments-paypal` · `payments-alipay` · `payments-wechatpay` · `payments-stripe-subscriptions`
**物流**：`shipping-zones` · `shipping-weight` · `shipping-free` · `shipping-pickup` · `shipping-local-delivery`
**搜索**：`search` · `search-meili` · `search-elastic`
**通知**：`notifications` · `notifications-sms` · `notifications-webhook`
**库存**：`inventory-fefo`
**用户体验**：`wishlist` · `returns-portal` · `abandoned-cart` · `sku-bundles` · `loyalty-redemption`
**定价**：`multi-currency-pricing`

完整一句话用途见 [docs/06-package-catalog.md](docs/06-package-catalog.md)。

## 测试 + 依赖检查

```bash
# 任何一个包
cd packages/<pkg> && composer install && vendor/bin/phpunit

# 全树依赖方向检查（CI 也跑同样脚本）
python3 - <<'PY'
import json, glob
layers, deps = {}, {}
for f in glob.glob('packages/*/composer.json'):
    d = json.load(open(f))
    m = d.get('extra',{}).get('acme',{}).get('module')
    if not m: continue
    layers[m['key']] = m.get('layer',99)
    deps[m['key']]   = m.get('depends',[])
bad=[(p, d) for p, ds in deps.items() for d in ds if d in layers and layers[d] > layers[p]]
print('VIOLATIONS:', bad if bad else 'none')
PY
```

## 当前状态

- **42 个包** 全部绿测
- **依赖方向** 0 违规
- **CI 矩阵** 35 个测试包 × PHP 8.3 / 8.4
- **业务层** 前端电商全栈完成，后端业务（CRM/ERP/Finance/LMS）按用户要求暂缓
