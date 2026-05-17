# acme/payments-paypal

> PayPal `PaymentGateway` implementation for [acme/payments](../payments). PayPal Orders v2 + Hosted Approval。

## 依赖
- [acme/payments](../payments)

直接走 REST，无 `paypal/paypal-checkout-sdk` 依赖。OAuth2 bearer token 用 Laravel cache 缓存到失效前 60 秒。

## 配置 `.env`
```
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_MODE=sandbox          # sandbox | live
PAYPAL_WEBHOOK_ID=WH-...     # PayPal Developer → App → Webhooks
PAYPAL_RETURN_URL=https://shop.example.com/orders/success
PAYPAL_CANCEL_URL=https://shop.example.com/cart
```

## 在 PayPal Developer 控制台
1. Apps & Credentials → 拿到 client id + secret
2. Webhooks → Add：URL `https://your-host/payments/paypal/webhook`
3. 订阅事件：`PAYMENT.CAPTURE.COMPLETED`、`PAYMENT.CAPTURE.DENIED`、`CHECKOUT.ORDER.APPROVED`、`CHECKOUT.ORDER.VOIDED`
4. 复制 webhook id → `PAYPAL_WEBHOOK_ID`

## 流程
1. 用户结账选 `payment_gateway=paypal`
2. `PayPalGateway::createIntent` 在 PayPal 建一个 Order，返回 `approve` 链接
3. 用户重定向到 PayPal，付款
4. PayPal 通过 webhook 回调；`parseWebhook` 调 PayPal `verify-webhook-signature` 接口校验
5. 校验通过 → `PaymentSucceeded` → `OrderPaid` → 后续

## 签名校验

PayPal 的 webhook 签名校验是**服务端二次调用**（不是 HMAC 本地算）。`PayPalClient::verifyWebhookSignature()` 调 PayPal `/v1/notifications/verify-webhook-signature`，给定一次性 webhook id + 完整原 headers + 原 event body，得 `verification_status: SUCCESS|FAILURE`。

若 `PAYPAL_WEBHOOK_ID` 留空，会**跳过校验**（仅 sandbox 测试用，生产请务必设置）。

## Sandbox 联调
1. `PAYPAL_MODE=sandbox`，凭据使用 PayPal Sandbox app
2. 在 [developer.paypal.com → Testing Tools → Sandbox Accounts](https://developer.paypal.com) 创建测试买家
3. 下单 → 跳到 sandbox.paypal.com → 用测试买家登录付款
4. 检查 webhook 是否回调，及 `acme_payments_transactions` 是否被标记 succeeded
