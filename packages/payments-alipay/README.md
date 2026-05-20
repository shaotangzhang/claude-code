# acme/payments-alipay

> Alipay 开放平台 Gateway 实现，对接 [acme/payments](../payments)。RSA2 签名，REST API，**无** alipay-sdk-php 依赖。

## 依赖
- [acme/payments](../payments)
- PHP ext: `openssl`（签 RSA2 用）

## 配置 `.env`

```
ALIPAY_APP_ID=2021000000000000
ALIPAY_APP_PRIVATE_KEY=/home/user/keys/alipay_app_private.pem   # 路径 或 直接贴 PEM
ALIPAY_PUBLIC_KEY=/home/user/keys/alipay_public.pem
ALIPAY_MODE=sandbox                                              # 'live' or 'sandbox'
ALIPAY_RETURN_URL=https://shop.example.com/orders/success
ALIPAY_NOTIFY_URL=https://shop.example.com/payments/alipay/webhook
```

`ALIPAY_PUBLIC_KEY` = **Alipay 平台发给你的公钥**（不是你自己的应用公钥）；只用来验 webhook 签名。

## 流程

```
checkout 选 payment_gateway=alipay
   │
   ▼
AlipayGateway::createIntent
   ├─ biz_content = { out_trade_no=tx_id, total_amount, subject,
   │                  passback_params(transaction_id, related_*) }
   ├─ params + RSA2 sign → URL
   └─ PaymentResult(pending, redirectUrl=…)
                     │
                     ▼
            用户跳转 Alipay 付款
                     │
                     ▼
       Alipay 异步回调 POST /payments/alipay/webhook
                     │
                     ▼
      AlipayGateway::parseWebhook
          ├─ AlipaySignature::verify($payload, $sign, $alipayPublicKey)
          ├─ 解 passback_params → transaction_id
          └─ status by trade_status (TRADE_SUCCESS|TRADE_FINISHED → succeeded)
                     │
                     ▼
           PaymentService::markSucceeded → PaymentSucceeded
                                                │
                                                ▼
                              checkout listener → OrderPaid → 后续
```

## 签名

`AlipaySignature::sign($params, $privateKey)`：

1. 去掉 `sign` + `sign_type`，按 key 升序，URL-form-style 串：`a=1&b=2&...`
2. `openssl_sign($payload, $sig, $key, OPENSSL_ALGO_SHA256)`
3. `base64_encode($sig)`

`verify` 反向用 Alipay 公钥。Webhook 必须开公钥校验（无 public key 配置时**拒收**），保证只有 Alipay 能触发 PaymentSucceeded。

## 沙箱联调
1. 在 https://open.alipay.com → 沙箱应用，拿到 APP ID + RSA 密钥对
2. `ALIPAY_MODE=sandbox` + 沙箱 endpoint
3. 用沙箱买家账号付款；验证 webhook 是否回调 + `acme_payments_transactions.status=succeeded`

## 不在 0.1 范围
- `alipay.trade.precreate`（扫码 / 二维码场景）—— biz_content 略有差异，写一个独立 method 即可
- 多商户子账号
- 凭证 PDF（账单文件下载）
