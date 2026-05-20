# acme/payments-stripe-subscriptions

> 把 [acme/membership](../membership) 的订阅周期交给 Stripe Subscriptions 自动续费。**完成订阅闭环**。

## 依赖
- [acme/membership](../membership)
- [acme/payments-stripe](../payments-stripe)（复用同一把 secret key + 签名算法）

## 数据

`acme_subs_stripe_links` —— 一行一对一映射 membership.Subscription ↔ Stripe Subscription：

| 列 | 用途 |
| --- | --- |
| `subscription_id` | acme membership 的 sub.id（唯一） |
| `stripe_customer_id` | Stripe customer |
| `stripe_subscription_id` | Stripe sub id（webhook 查找用） |
| `stripe_price_id` | 对应的 Stripe Price |
| `status` | pending / active / past_due / canceled |
| `current_period_end` | Stripe 一手数据 |
| `last_invoice_id` | 幂等去重 |

## 配置

```env
# Stripe 凭据复用 payments-stripe
STRIPE_SECRET_KEY=sk_live_...
STRIPE_API_BASE=https://api.stripe.com/v1

# 订阅 webhook 用独立的 signing secret（在 Stripe Dashboard 新建一个独立 endpoint）
STRIPE_SUBS_WEBHOOK_SECRET=whsec_...
STRIPE_SUBS_SUCCESS_URL=https://shop.example.com/account/membership
STRIPE_SUBS_CANCEL_URL=https://shop.example.com/membership/plans
```

membership 的每个 Plan 在 `meta_json.stripe_price_id` 上记一条 Stripe Price id（在 Dashboard 预先创建）。

## 完整闭环

```
1. 用户在 /membership/plans 点订阅
2. membership.SubscriptionService::start
       ├─ trial / free → 直接 Active（不进入此包）
       └─ paid 无 trial → emit PaymentDue
3. (host) 把 PaymentDue 路由给本包的 Linker（或本包监听）
       Linker::startCheckout
       └─ Stripe createCustomer + createSubscriptionCheckoutSession
       → redirect URL
4. 用户 Stripe Checkout 付款 → success_url
5. Stripe 推 webhook → /payments/stripe-subs/webhook
       ├─ customer.subscription.created  → 记 stripe_subscription_id
       ├─ invoice.payment_succeeded      → dispatch Membership\PaymentReceived
       │     └─ membership 监听器 recordPayment → 推进 period
       ├─ invoice.payment_failed         → status=past_due
       └─ customer.subscription.deleted  → status=canceled
6. 后续每月 Stripe 自动扣款；每次 invoice 成功 → webhook → PaymentReceived → 周期延长
```

## 签名

复用 `Acme\PaymentsStripe\StripeSignature`（同 HMAC-SHA256 算法），但用**独立的 endpoint signing secret**——在 Stripe Dashboard 为 `/payments/stripe-subs/webhook` 新建一个 endpoint 拿对应密钥。

## 在 Stripe Dashboard

1. Products → 为每个 Plan 创建 Price，把 id 填回 `Plan.meta_json.stripe_price_id`
2. Developers → Webhooks → 添加 endpoint：
   - URL: `https://your-host/payments/stripe-subs/webhook`
   - Events: `customer.subscription.created/updated/deleted`、`invoice.payment_succeeded`、`invoice.payment_failed`
3. 复制 signing secret → `STRIPE_SUBS_WEBHOOK_SECRET`

## 不在 0.1
- 自动响应 membership `SubscriptionStarted` 触发 Linker（host 项目目前要手动调 `startCheckout`，或在自家代码里桥接）—— 0.2 加一个 listener 完成自动化
- 暂停 / 恢复 Stripe-side（暂停在 Stripe 上不可逆，需要切换到 trial），目前只覆盖正常生命周期
- 多 Price tier 切换（升降级）

## 0.2 路线

- `StripeSubscriptionLinker::startCheckout` 自动挂在 `Membership\Events\SubscriptionStarted` 上
- 用户取消 membership 时 → 调 Stripe cancelSubscription
- 失败发票自动重试（Stripe Smart Retries 已是默认，但本包也可加 listener 提醒）
