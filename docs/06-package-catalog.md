# 06 · 包目录

按层 + 一句话用途 + 关键依赖。详情见各包 README。

## Layer 0 · 基础

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/contracts` | 跨包接口与 DTO 池；零实现 | — |
| `acme/support` | 共享 Trait（HasUlid / Sluggable / HasTranslations）+ helpers | contracts |
| `acme/starter` | `PackageServiceProvider` 基类、模块注册中心、`acme:modules/install/uninstall` 命令 | contracts · support |

## Layer 1 · 身份

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/auth` | 用户、登录、注册、2FA scaffolding、邀请、会话日志 | starter |
| `acme/rbac` | 角色 / 权限 / capability 注册中心 + Gate 自动绑定 | auth |
| `acme/admin` | 后台外壳（导航聚合、Dashboard、布局） | rbac |
| `acme/user-center` | 前台个人中心外壳 | auth |

## Layer 2 · 内容

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/cms-core` | Theme / Layout / Page / Slot / Block / Widget 渲染管线 | rbac |
| `acme/cms-admin` | 页面编辑 / 版本 / 草稿 / 发布 / 回滚 / 主题切换 / `acme:theme:make` | admin · cms-core |
| `acme/media` | 文件登记表 + 多态附件 | rbac |
| `acme/i18n` | 内容字段翻译（多态 translations 表 + 回退链） | starter |
| `acme/seo` | sitemap / robots / canonical / OG-card | cms-core |

## Layer 3 · 业务

### 核心域

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/blog` | 文章 / 分类 / 标签 / 评论 / RSS / 订阅；3 个 CMS Block | cms-core |
| `acme/catalog` | 产品 / 品牌 / 分类 / SKU / 多图，展示用 | cms-core · media |
| `acme/membership` | 会员等级 + 订阅计划 + 计费状态机（payment-gateway agnostic） | rbac · cms-core |
| `acme/cart` | 购物车 + 优惠券 + 可插拔税费/运费 + 调整器 + 礼品行 | catalog |
| `acme/payments` | 支付网关抽象 + Manual gateway + 交易账本 + Webhook 装置 | rbac |
| `acme/checkout` | 订单 / 发票草稿 / Order 状态机 + payment 桥接 | cart · membership · payments |
| `acme/commerce` | 多仓库存 / 退换 / 评价 / 营销活动 / 积分 + StockAllocator 抽象 | checkout |

### 支付网关

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/payments-stripe` | Stripe Hosted Checkout + 退款 webhook | payments |
| `acme/payments-paypal` | PayPal Orders v2 + Hosted Approve | payments |
| `acme/payments-alipay` | Alipay PC 网关 + RSA2 签名 | payments |
| `acme/payments-wechatpay` | 微信支付 v3 (Native 扫码) + AEAD-AES-256-GCM webhook 解密 | payments |
| `acme/payments-stripe-subscriptions` | membership ↔ Stripe Subscriptions 桥接（自动续费闭环） | membership · payments-stripe |

### 物流方法

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/shipping-zones` | 国别 → 区 → 多档费率（含 subtotal 窗口） | cart |
| `acme/shipping-weight` | SKU 重量表 + 重量档费率 | cart · catalog |
| `acme/shipping-free` | 阈值 / 国别可配的免邮 | cart |
| `acme/shipping-pickup` | 门店 / 仓库自提点 | cart |
| `acme/shipping-local-delivery` | 同城配送（邮编前缀 + 多速度档） | cart |

### 搜索

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/search` | 索引表 + Driver 抽象 + 默认 `DatabaseDriver` (LIKE) + reindex 命令 | catalog |
| `acme/search-meili` | MeiliSearch v1 driver；rebind `Driver::class` 即换 | search |
| `acme/search-elastic` | Elasticsearch 7/8 driver | search |

### 通知

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/notifications` | Channel 抽象 + Mail/Log channel + Dispatcher + 7 个上游事件桥接 | checkout · commerce · blog |
| `acme/notifications-sms` | Twilio-兼容 SMS channel | notifications |
| `acme/notifications-webhook` | HMAC-SHA256 签名的 Webhook channel | notifications |

### 库存策略

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/inventory-fefo` | First-Expired-First-Out 批次库存 + 跨仓调拨 + 近期到期自动 timed_discount | commerce |

### 用户体验 / 增值能力

| Package | 一句话 | depends |
| --- | --- | --- |
| `acme/wishlist` | 多列表心愿单 + move-to-cart | cart |
| `acme/abandoned-cart` | 弃车检测 + 多轮提醒 + 每轮自动 coupon | cart · notifications |
| `acme/sku-bundles` | "打包 SKU" 销售 + 自动 saving 折扣 | cart · catalog |
| `acme/loyalty-redemption` | 积分抵现（挂 cart 的 `CartAdjustmentProvider`） | cart · commerce · checkout |
| `acme/returns-portal` | 用户中心 RMA 自助门户（消费 commerce 的 ReturnService） | commerce · checkout |
| `acme/multi-currency-pricing` | 同 SKU 多币种独立定价（替换 cart 的 `PriceResolver`） | cart · catalog |

## 已经验证的抽象（contracts）

| Contract | 实现数 | 实现包 |
| --- | --- | --- |
| `Payments\PaymentGateway` | 5 | payments (Manual) · payments-stripe · payments-paypal · payments-alipay · payments-wechatpay |
| `Commerce\ShippingMethod` | 5+1 | cart (Flat) + shipping-zones · shipping-weight · shipping-free · shipping-pickup · shipping-local-delivery |
| `Notifications\Channel` | 4 | notifications (Mail+Log) · notifications-sms · notifications-webhook |
| `Search\Driver` | 3 | search (Database) · search-meili · search-elastic |
| `Commerce\StockAllocator` | 2 | commerce (basic) · inventory-fefo |
| `Commerce\CartAdjustmentProvider` | 3 | commerce (Campaigns) · loyalty-redemption · sku-bundles |
| `Commerce\CartGiftProvider` | 1 | commerce (Bxgy) |
| `Commerce\PriceResolver` | 2 | cart (Default) · multi-currency-pricing |
| `Module\NavigationRegistry` | 1 | admin |
| `Module\CapabilityRegistry` | 1 | rbac |
| `Cms\BlockRegistry` / `LayoutRegistry` / `ThemeRegistry` / `WidgetRegistry` | 1 each | cms-core |

每个 contract 都已被 ≥ 1 个 host 包或 sibling 包实现过——抽象表面经过实战。
