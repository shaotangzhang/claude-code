# acme/payments

> 支付网关抽象 + 交易账本 + Webhook 装置。**自带 Manual 网关**，真正接 Stripe / WeChatPay 写一个子包即可。

## 依赖
- [acme/rbac](../rbac)

## 核心抽象

| Contract（在 `acme/contracts`） | 用途 |
| --- | --- |
| `Acme\Contracts\Payments\PaymentGateway` | 任何网关实现的接口：`createIntent / parseWebhook / refund` |
| `Acme\Contracts\Payments\PaymentIntent` | 一次支付意图的 DTO |
| `Acme\Contracts\Payments\PaymentResult` | 网关返回的结果（pending/succeeded/failed） |

`GatewayRegistry` 是网关的容器化注册中心；任何子包的 SP 调用 `$registry->register(new MyGateway())`。

## 账本

`acme_payments_transactions` —— 一条记录 = 一笔意图，覆盖所有网关 + 所有相关业务（订单 / 订阅 / 充值 / ...）：

| 列 | 用途 |
| --- | --- |
| `gateway` | "manual" / "stripe" / ... |
| `related_type` + `related_id` | 关联到哪个业务对象 |
| `gateway_reference` | 网关那边的 id（charge / intent / receipt） |
| `status` | pending / succeeded / failed / refunded |

## 事件（**这是与上层业务包的契约**）

| Event | 何时 | 关键字段 |
| --- | --- | --- |
| `PaymentSucceeded` | 钱到账 | `relatedType` + `relatedId` 让监听者认领 |
| `PaymentFailed` | 失败 | 同上 + `reason` |
| `PaymentRefunded` | 退款 | 金额 + 币种 |

`acme/checkout` 监听 `PaymentSucceeded` 并按 `relatedType=order` 处理。
`acme/membership` 自己有 `PaymentReceived`；下个版本会加 `payments-membership-bridge` 把 `PaymentSucceeded` 翻译过去。

## 路由

| Method | URI | 用途 |
| --- | --- | --- |
| POST | `/payments/{gateway}/webhook` | 网关回调入口；对应网关解析签名 + payload |
| POST | `/admin/payments/transactions/{tx}/confirm` | 手动确认（Manual gateway） |
| POST | `/admin/payments/transactions/{tx}/reject`  | 手动拒绝 |

## 写一个新网关

```php
final class StripeGateway implements PaymentGateway {
    public function key(): string { return 'stripe'; }
    public function createIntent(PaymentIntent $i): PaymentResult { /* HTTP call */ }
    public function parseWebhook(array $payload, array $headers): array { /* verify HMAC */ }
    public function refund(...) { /* HTTP call */ }
}

// In your StripeServiceProvider::packageBoot():
$this->app->make(GatewayRegistry::class)->register(new StripeGateway());
```

子包的 module key 例如 `payments-stripe`，layer 3，depends 上 `payments`。
