# acme/commerce  (0.2)

> 中型电商的"加料层"——多仓库存、退换货、评价、**自动应用的营销活动**、积分。**通过监听 checkout 事件接入；通过 `CartAdjustmentProvider` 接入 cart**——不修改任何上游一行代码。

## 依赖
- [acme/checkout](../checkout)（监听其事件）
- 通过 checkout 间接依赖 catalog + cart + membership + payments

## 数据模型（9 张表）

| 表 | 用途 |
| --- | --- |
| `acme_commerce_warehouses` | 仓库主数据 |
| `acme_commerce_stock_levels` | 每 SKU × 仓库 的 on_hand + reserved |
| `acme_commerce_stock_movements` | **不可变审计**：每次库存变动一条 |
| `acme_commerce_returns` + `_items` | RMA 状态机 |
| `acme_commerce_reviews` | 评价（含审核状态） |
| `acme_commerce_campaigns` | 营销活动（rules_json 描述规则；引擎在 0.2） |
| `acme_commerce_loyalty_accounts` + `_transactions` | 积分账本，每次变动留 audit row |

## 库存核心机制

```
order.placed  → (do nothing; cart already snapshotted)
order.paid    → StockService::reserveForOrder  (reserved += qty)
order.fulfilled → StockService::shipForOrder   (reserved -= qty, on_hand -= qty)
order.canceled → StockService::releaseForOrder (reserved -= qty)
```

每一步都写一行 StockMovement（不可变），状态可以从 movements 重放。

## 服务

| 服务 | 关键方法 |
| --- | --- |
| `StockService` | `receive / adjust / reserveForOrder / shipForOrder / releaseForOrder` —— 全部事务 + 锁 + 审计 |
| `ReturnService` | `request / approve / markReceived / reject / refund(amount)` —— refund 调 `PaymentService::refund` |
| `ReviewService` | `submit / approve / markSpam` |
| `LoyaltyService` | `award / redeem` —— 每次变动写 LoyaltyTransaction |

## 事件
| Event | 触发位置 |
| --- | --- |
| `StockReserved` | 订单付款后预留成功 |
| `StockLow` | 单 SKU 在某仓库的 available ≤ 阈值 |
| `PointsAwarded` | 积分发放成功 |
| `ReviewSubmitted` | 评价落库（pending 或 approved 都触发） |
| `ReturnRequested` | RMA 创建 |

## 营销引擎（0.2 已实装）

`Campaign` + `rules_json` 数据模型来自 0.1；**0.2 接上了自动应用引擎**：

```
                                                cart.recalculate()
                                                       │
                                                       ▼
                                  TotalsCalculator 查 AdjustmentRegistry
                                                       │
                                                       ▼
                                         CampaignProvider.adjustmentsFor()
                                                       │
                          ┌─── BundleEvaluator         │
                          ├─── TimedDiscountEvaluator  │  (查所有 active 且在 starts/ends 窗口内的 campaign)
                          ├─── ...                     │
                                                       ▼
                                  [CartAdjustment[]] 累加到 discount/shipping
```

### 已实现的两种类型

| `Campaign::type` | rules_json shape | 行为 |
| --- | --- | --- |
| `bundle` | `{required_sku_ids:[...], discount_cents:N}` | 所有指定 SKU 同时存在时立减 N |
| `timed_discount` | `{scope:'cart'\|'sku', sku_ids:[...], percent:N}` | 整车或特定 SKU 折扣 N% |

### 写一个新规则

1. 实现 `Acme\Commerce\Campaigns\RuleEvaluator`
2. 在 `CommerceServiceProvider::packageRegister()` 的 `$this->app->tag(...)` 列表里加上你的类
3. 在 `Campaign::type` 上加一个常量与之对应

`bxgy` (buy X get Y) 和 `freebie`（赠品 / 免运）涉及"插入新行"语义，留 0.3。

### 与 cart coupons 的关系

- **coupons**：用户主动输入码兑换，存在 cart_coupons pivot；可解除
- **campaigns**：自动应用，无 pivot；每次 recalc 重新评估；用户不可解除（除非运营下线 campaign）

两者**都通过 discount_cents 累加**，cart 总额永远不会被打成负数（discount 上限 = subtotal）。

## 推荐系统
**不在 0.1 范围**。下一个版本接 Elasticsearch / pgvector 时新开 `acme/commerce-recommendations` 子包。

## 后续 0.2 路线
- Campaign 自动应用（CartAdjustmentProvider）
- 全部 admin UI（仓库 / 库存调整 / RMA 审核 / 评价审核 / 积分调账）
- 推荐子包
- 多仓选仓策略（priority / proximity / split-fulfilment）
