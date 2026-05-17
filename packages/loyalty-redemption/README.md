# acme/loyalty-redemption

> 用 commerce 的 loyalty points 抵 cart 上的现金折扣。**完全通过 cart 0.2 的 `CartAdjustmentProvider` 接入，不修改 cart / commerce 任何代码**。

## 依赖
- [acme/cart](../cart) ≥ 0.2
- [acme/commerce](../commerce)（积分账户）
- [acme/checkout](../checkout)（OrderPaid 闭环）

## 流程

```
用户在购物车点 "用 500 点抵 ¥5"
       │
       ▼
POST /cart/loyalty/apply  ┬──→ LoyaltyRedemptionService::apply
                           │     ├─ 校验余额、subtotal 上限
                           │     └─ 写 cart.meta_json.loyalty_redemption
                           │
                           ▼
                  TotalsCalculator 重算
                           │
                           ▼
            LoyaltyRedemptionProvider 返回 -500c 折扣
                           │
                           ▼
                   cart 总价 = -¥5
                           │
                       下单付款
                           │
                           ▼
                       OrderPaid
                           │
                           ▼
            HandleOrderPaid 监听器
                ├─ LoyaltyService::redeem(500)  (真正扣点)
                └─ RedemptionState::clear($cart)
```

**点数只在订单付款后真扣**——之前都是"意向"。撤销 / 付款失败 → 状态自动作废，不会乱扣。

## 配置

| Key | 默认 | 用途 |
| --- | --- | --- |
| `acme.commerce.loyalty.redeem_cents_per_point` | 1 | 每点等于多少分（即 100 点 = ¥1） |

## 路由

| Method | URI | Name |
| --- | --- | --- |
| POST   | `/cart/loyalty/apply` | `acme.cart.loyalty.apply` |
| DELETE | `/cart/loyalty/`      | `acme.cart.loyalty.clear` |

## 多 provider 叠加

cart 0.2 的 `CartAdjustmentProvider` 是追加式 —— campaigns + loyalty + 未来的 member-discount 三个 provider 同时返回 adjustments，TotalsCalculator 一并扣到 `discount_cents`（封顶 = subtotal，不会负数）。
