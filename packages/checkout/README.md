# acme/checkout

> 下单 + 订单状态机 + 发票草稿。**`Cart → Order → Payment` 整条链路在此封口**。

## 依赖
- [acme/cart](../cart)（消费 cart 数据）
- [acme/membership](../membership)（共存；订阅与一次性订单互不干扰）
- [acme/payments](../payments)（发起 PaymentIntent，监听 PaymentSucceeded）

## 数据模型

```
Order (placed_at, paid_at, fulfilled_at, canceled_at)
  ├── OrderItem[]   — snapshot of SKU at submit; survives SKU deletion
  └── Invoice       — draft → issued → paid → void
```

订单的 `currency / totals / addresses` 全部从 cart 一次性快照过来，**不依赖 cart 之后的变动**。

## 状态机

```
                 ┌─ failed_payment
                 │
pending_payment ─┼─ paid ─── fulfilled
                 │       └─ refunded (terminal)
                 └─ canceled (terminal)
```

`OrderStatus` enum 有 `isTerminal()` 与 `isPaid()` 用于守护过渡。

## 关键流程

```
CartService.markConverted ◄──┐
                             │
CheckoutService::submit() ──→ Order (pending_payment)
                              │
                              └─→ PaymentService::createIntent('order', orderId, ...)
                                       │
                                       └─→ ManualGateway (or Stripe / 等) 返回 redirect / pending
                                              │
                                              ▼
                                          (用户支付)
                                              │
                                              ▼
                                         Webhook (/payments/{gateway}/webhook)
                                              │
                                              ▼
                                     PaymentService::markSucceeded
                                              │
                                              └→ dispatch PaymentSucceeded
                                                     │
                                                     ▼
                                      HandlePaymentSucceeded (本包监听)
                                                     │
                                                     ▼
                                       OrderService::markPaid()
                                                     │
                                                     └→ dispatch OrderPaid
                                                           │
                                                           └→ 下游（库存扣减 / 发货 / 通知）
```

## 服务

| 服务 | 职责 |
| --- | --- |
| `CheckoutService::submit($cart, $billing, $shipping, $shippingKey, $gatewayKey)` | 快照 cart → 建 Order → 建 Invoice draft → mark cart converted → 调 PaymentService |
| `OrderService::markPaid / markFulfilled / cancel` | 状态机过渡（幂等，事务包裹，派事件） |

## 事件

| Event | 含义 |
| --- | --- |
| `OrderPlaced` | 订单写入 DB（支付未必完成） |
| `OrderPaid` | 支付完成；下游可以扣库存 / 发邮件 |
| `OrderFulfilled` | 已发货 / 已交付 |
| `OrderCanceled` | 取消（含 reason） |

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET  | `/checkout` | `acme.checkout.show` |
| POST | `/checkout/place` | `acme.checkout.place` |
| GET  | `/orders` | `acme.checkout.orders.index` |
| GET  | `/orders/{order}` | `acme.checkout.orders.show` |

## 不在范围（留到 M9 commerce / 0.2）
- 库存扣减（commerce 监听 `OrderPaid` 实现）
- 发票 PDF 生成（hook 已留好，模板与 mailer 由宿主项目装）
- 多支付分单 / 押金 / 分期
