# 03 · 迭代路线图（实际交付状态）

每个里程碑分支命名 `pkg/m<N>-...`，合入 main 后保留作 fork 起点。

## ✅ 已交付（main = c3d1794）

| M | 包 / 主题 | 状态 |
| --- | --- | --- |
| M0 | contracts · support · starter | ✅ |
| M1 | auth · rbac · admin · user-center | ✅ |
| M2 | cms-core · cms-admin · media · i18n · seo | ✅ |
| M3 | cms-admin（编辑 / 版本 / 发布 / 回滚 / menu / theme） | ✅ |
| M4 | blog（articles · categories · tags · comments · rss · subscribe） | ✅ |
| M5 | catalog（products · brands · skus · 4 个 CMS Block） | ✅ |
| M6 | membership（tiers · plans · subscription 状态机） | ✅ |
| M7 | cart（items · coupons · merge-on-login · 可插拔 tax/shipping） | ✅ |
| M8 | payments + checkout（Manual gateway · Order 状态机） | ✅ |
| M9 | commerce（inventory · returns · reviews · campaigns · loyalty） | ✅ |
| M10 | campaign 自动应用引擎（cart 0.2 + commerce 0.2 + CartAdjustmentProvider） | ✅ |
| M11 | payments-stripe · payments-paypal · wishlist | ✅ |
| M12 | search · notifications · shipping-zones · shipping-weight · cart 0.3 | ✅ |
| M13 | loyalty-redemption · bxgy/freebie · multi-currency-pricing · apps/demo 骨架 | ✅ |
| M14 | 礼品行（cart 0.5 + commerce 0.4）· shipping-free/pickup/local-delivery · notifications-sms/webhook | ✅ |
| M15 | abandoned-cart 0.1（检测 + 恢复 token + 单轮提醒） | ✅ |
| M16 | sku-bundles · abandoned-cart 0.2（多轮 + coupon）· inventory-fefo · StockAllocator 抽取 | ✅ |
| M17 | search-meili · payments-alipay · payments-wechatpay | ✅ |
| M18 | returns-portal · payments-stripe-subscriptions · search-elastic · inventory-fefo 0.2 · 退款 webhook 闭环 | ✅ |
| M19 | apps/demo Laravel 12 骨架 + 文档刷新 | ✅ |

总计：**42 个 acme/* 包** + 1 个 demo host。35 个包在 CI 矩阵跑 phpunit；依赖方向检查 0 violations。

## ⏸ 推迟（用户决定先不做）

| M | 包 | 备注 |
| --- | --- | --- |
| M20 | acme/crm | 客户、商机、销售管线 |
| M21 | acme/erp | 采购、库存、HR |
| M22 | acme/finance | 总账、发票、对账、报表 |
| M23 | acme/lms | 课程、章节、测验、证书 |

这四个模块在 [docs/01-architecture.md](01-architecture.md) 里仍占位 layer 3 后端业务；按现有 layer 3 业务包模式开即可。

## 横向扩展（已落地，不归任何里程碑）

每个跨包抽象都有 ≥ 2 个实现验证：

| 抽象方向 | 实现包 |
| --- | --- |
| 支付网关 (`PaymentGateway`) | payments (Manual) · payments-stripe · payments-paypal · payments-alipay · payments-wechatpay · payments-stripe-subscriptions |
| 物流方法 (`ShippingMethod`) | cart (Flat) · shipping-zones · shipping-weight · shipping-free · shipping-pickup · shipping-local-delivery |
| 搜索引擎 (`Driver`) | search (Database) · search-meili · search-elastic |
| 通知通道 (`Channel`) | notifications (Mail+Log) · notifications-sms · notifications-webhook |
| 库存策略 (`StockAllocator`) | commerce (basic) · inventory-fefo |
| Cart 调整器 (`CartAdjustmentProvider`) | commerce (Campaigns) · loyalty-redemption · sku-bundles |
| Cart 礼品 (`CartGiftProvider`) | commerce (BxGy gifts) |
| 价格策略 (`PriceResolver`) | cart (Default) · multi-currency-pricing |

## 后续可选方向

按价值序：

1. **真跑 apps/demo + 抓集成 bug** ← 当前推荐
2. inventory-fefo 0.3（退货回库 batch 反向归还）
3. returns-portal 0.2（照片附件 + 物流单号）
4. payments-stripe-subscriptions 0.2（监听 `SubscriptionStarted` 自动 startCheckout）
5. abandoned-cart 0.3（按用户 timezone 决定提醒时段）
6. CRM/ERP/Finance/LMS（M20-M23，企业后端）

## 跨里程碑横向工作流

每个里程碑都顺手做：

- 升级影响包的 `extra.acme.module.version`
- contracts 加东西时 bump minor
- CI 矩阵加新包
- 跑全树依赖方向检查（应 0 violations）
- README + docs 同步（特别是 [docs/06-package-catalog.md](06-package-catalog.md)）
- 合并到 main 通过 fast-forward（无 merge commit，保持线性历史）

## 验收回顾（每个里程碑都过）

- ✅ 该里程碑所列包 `composer install` 成功
- ✅ phpunit 全绿
- ✅ 上游包没回归
- ✅ 依赖方向 0 violations
- ✅ 在 `pkg/m<N>-...` 分支推送 + FF main + 远端同步
