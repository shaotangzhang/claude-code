# acme/payments-wechatpay

> 微信支付 V3 Gateway，对接 [acme/payments](../payments)。RSA-SHA256 请求签名 + AEAD-AES-256-GCM webhook 解密。**无** wechatpay-sdk-php 依赖。

## 依赖
- [acme/payments](../payments)
- PHP ext: `openssl`

## 配置 `.env`

```
WECHATPAY_MCH_ID=160000000             # 商户号
WECHATPAY_APP_ID=wx1234567890abcdef    # 公众号 / 小程序 / APP appid
WECHATPAY_SERIAL_NO=ABCDEF...           # 商户 API 证书 serial_no
WECHATPAY_PRIVATE_KEY=/home/user/keys/apiclient_key.pem   # 路径 或 PEM
WECHATPAY_APIV3_KEY=32CHARLONGKEY...    # APIv3 密钥（AEAD-AES-256-GCM 解密用）
WECHATPAY_PLATFORM_PUBLIC_KEY=/home/user/keys/wechatpay_platform.pem
WECHATPAY_TRADE_TYPE=native             # native | jsapi | h5
WECHATPAY_NOTIFY_URL=https://shop.example.com/payments/wechatpay/webhook
```

## 流程（native 扫码）

```
checkout 选 payment_gateway=wechatpay
   │
   ▼
WeChatPayGateway::createIntent
   ├─ body: { out_trade_no, mchid, appid, amount.total, amount.currency, attach(json) }
   ├─ POST /v3/pay/transactions/native (RSA-SHA256 签 header)
   └─ PaymentResult(pending, redirectUrl=<code_url>)   # 给前端渲染二维码
                     │
                     ▼
            用户扫码付款
                     │
                     ▼
        微信支付 POST /payments/wechatpay/webhook
                     │
                     ▼
       WeChatPayGateway::parseWebhook
          ├─ verifyWebhook($ts, $nonce, $rawBody, $sig, platformPublicKey)
          ├─ decryptResource(ciphertext, assoc, nonce, apiV3Key)  ← AES-256-GCM
          ├─ 解 attach → transaction_id
          └─ status by trade_state (SUCCESS → succeeded)
                     │
                     ▼
           PaymentService::markSucceeded → PaymentSucceeded
                                                │
                                                ▼
                              checkout listener → OrderPaid → 后续
```

## 签名 / 验签 / 解密

`WeChatPaySignature::signRequest()` 实现 V3 的 `WECHATPAY2-SHA256-RSA2048` 鉴权头：

```
signature_string = method + "\n" + url_path + "\n" + ts + "\n" + nonce + "\n" + body + "\n"
signature        = base64( RSA-SHA256( signature_string, merchant_private_key ) )
Authorization: WECHATPAY2-SHA256-RSA2048 mchid="...",nonce_str="...",
               timestamp="...",serial_no="...",signature="..."
```

`verifyWebhook()` 用微信平台公钥验 `Wechatpay-Signature` 头（同算法）。`decryptResource()` 用 APIv3 key + GCM 解密 `resource.ciphertext`（tag = 末 16 字节）。

控制器把 raw body 通过 `$headers['__raw_body']` 透传给 `parseWebhook`，避免 JSON 编/解码扰动签名。

## 沙箱联调

微信支付**没有**沙箱付款（提供的只是固定金额的测试），生产联调一般直接走低额真单。建议：

1. 用 `WECHATPAY_TRADE_TYPE=native` 跑通扫码场景
2. 路由 `/payments/wechatpay/webhook` 公网可达 + HTTPS
3. 第一笔下 1 分钱看回调能否解密 & 落库

## 不在 0.1 范围

- jsapi / h5 路径的不同 biz 字段差异（写 sibling method 即可）
- 分账 / 投诉单 / 营销
- 多商户分账户（`sp_mchid` 服务商场景）
