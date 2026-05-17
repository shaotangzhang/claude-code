# acme/payments-stripe

> Stripe `PaymentGateway` implementation for [acme/payments](../payments). Hosted Checkout 模式（最简集成）。

## 依赖
- [acme/payments](../payments)

**不**依赖 `stripe/stripe-php` —— 直接走 Stripe REST API，CI 更轻。需要更高级特性（subscriptions / connect / billing portal）时再引官方 SDK。

## 配置 `.env`
```
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SUCCESS_URL=https://shop.example.com/orders/success
STRIPE_CANCEL_URL=https://shop.example.com/cart
```

## 在 Stripe 控制台
1. Developers → Webhooks → Add endpoint
2. URL：`https://your-host/payments/stripe/webhook`（路由由 `acme/payments` 提供）
3. 选事件：`checkout.session.completed`、`payment_intent.succeeded`、`payment_intent.payment_failed`、`checkout.session.expired`
4. 复制 signing secret → `STRIPE_WEBHOOK_SECRET`

## 安装后
`StripeServiceProvider::packageBoot()` 会自动把 `StripeGateway` 注册到 `GatewayRegistry`。下单时把 `payment_gateway=stripe` 传给 `/checkout/place`，用户会被重定向到 Stripe Hosted Checkout，付款完成后 Stripe 通过 webhook 回调，整条管线（`PaymentSucceeded` → `OrderPaid` → 库存预留 + 积分发放）一路触发。

## 签名校验

`StripeSignature::verify($raw, $header, $secret, $tolerance)` —— 实现与 stripe-php 等价的方案：
- 解析 `t=<unix>,v1=<hmac>` 头
- HMAC-SHA256 over `"{t}.{rawBody}"`
- 容差时间内拒绝重放
- 多个 v1 候选有一个匹配即通过

`WebhookController` 把 raw body 通过 `$headers['__raw_body']` 透传给 `parseWebhook`。

## 进入生产前检查

- [ ] secret_key / webhook_secret 都在生产 `.env`
- [ ] success_url / cancel_url 是真实路由
- [ ] webhook endpoint 已在 Dashboard 注册，且 signing secret 一致
- [ ] HTTPS 在反代/CDN 上正确终结（Stripe 仅信任 https）
- [ ] 测试单 → 跑通 `/checkout/place` → Stripe Hosted Checkout → 回 success_url → webhook 触发 PaymentSucceeded
