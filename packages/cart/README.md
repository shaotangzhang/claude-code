# acme/cart

> 购物车：游客 / 登录态合并、优惠券、可插拔税费 / 运费策略。**结账由 checkout 包消费此包的状态**。

## 依赖
- [acme/catalog](../catalog)（SKU 与价格）
- [acme/cms-core](../cms-core)（mini-cart Block）

## 数据模型

```
Cart (1 per user OR per guest token)
  ├── CartItem[]       — line items, snapshotted unit price
  ├── Coupon[]         — currently applied (pivot stores discount amount)
  └── denorm totals    — subtotal / discount / tax / shipping / total
```

- 一个用户同时只有一个 `status=active` 的 Cart
- 一个 guest 通过 cookie token (`acme_cart`) 持有一个 cart
- **登录瞬间**：Login 事件 → `MergeOnLogin` listener → `CartMerger` 把游客 cart 折进用户 cart，游客 cart 标记 `merged`

## 状态机

```
active ── checkout 完成 ──→ converted
       └─ 登录合并    ──→ merged (源 cart)
       └─ 长期未动    ──→ abandoned (留给 0.2 的定时任务标记)
```

## 可插拔策略（核心扩展点）

两个 contract，默认两个简单实现：

| Contract | 默认实现 | 替换姿势 |
| --- | --- | --- |
| `Acme\Contracts\Commerce\TaxCalculator` | `FlatRateTax`（一刀切 basis points） | 在宿主 SP `bind(TaxCalculator::class, YourTax::class)` |
| `Acme\Contracts\Commerce\ShippingCalculator` | `FlatRateShipping`（一刀切 + 阈值免运） | 同上 |

任何"按目的地"、"按重量"、"按时段"的策略都是替换默认实现的事情，**不**需要碰本包代码。

## 优惠券

`Coupon` 支持 `percent` 与 `fixed`，可配最低门槛、有效期、最大使用次数、币种约束。
- `CouponService::apply()` 校验所有约束、写 pivot、重算 totals、派 `CouponApplied` 事件
- `CouponService::remove()` 同理

## 中间件 `CartIdentifier`

挂在 `/cart/*` 路由组。每个请求保证：
1. 当前请求绑定一个 Cart（cookie 或 user_id）
2. Cart 被绑定到容器 `app(Cart::class)`，任何控制器 / Block 都可直接注入

## 与 checkout 的对接（M8）

checkout 包将：
1. 注入 `Cart`
2. 接到收货 / 支付方式后，重算 totals（传入 `Address` + 选中的 `ShippingOption.key`）
3. 创建 Order，对账 totals
4. 调 `CartService::markConverted($cart)`

本包**不**碰订单概念。

## 事件
| Event | 何时 |
| --- | --- |
| `ItemAdded` / `ItemUpdated` / `ItemRemoved` | 任何 line item 改动 |
| `CouponApplied` / `CouponRemoved` | 券变更 |
| `CartMerged` | 登录合并完成 |

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET    | `/cart`                  | `acme.cart.show` |
| POST   | `/cart/items`            | `acme.cart.items.add` |
| PUT    | `/cart/items/{item}`     | `acme.cart.items.update` |
| DELETE | `/cart/items/{item}`     | `acme.cart.items.remove` |
| POST   | `/cart/coupons`          | `acme.cart.coupons.apply` |
| DELETE | `/cart/coupons/{coupon}` | `acme.cart.coupons.remove` |

## 多币种

一个 Cart 锁定一个 currency（首件商品加入时确立）。加入不同 currency 的 SKU 会被拒绝。"切换币种" = 新建 Cart——避免 4 种货币混算的复杂度。
