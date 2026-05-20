<?php

declare(strict_types=1);

return [
    // Merchant ID (商户号)
    'mch_id' => env('WECHATPAY_MCH_ID'),

    // Public app id  (公众号 / APP / 小程序的 appid)
    'app_id' => env('WECHATPAY_APP_ID'),

    // 商户 API 证书序列号 (Wechat 后台下载证书时附带的 serial_no)
    'serial_no' => env('WECHATPAY_SERIAL_NO'),

    // 商户 API 私钥 PEM (路径或 PEM 内容)
    'private_key' => env('WECHATPAY_PRIVATE_KEY'),

    // APIv3 key — used for AEAD-AES-256-GCM decryption of webhook resource.
    'apiv3_key' => env('WECHATPAY_APIV3_KEY'),

    // Wechat platform public key PEM (used to verify webhook signatures).
    'platform_public_key' => env('WECHATPAY_PLATFORM_PUBLIC_KEY'),

    // Trade type:
    //   'native' = QR code scan        → /v3/pay/transactions/native
    //   'jsapi'  = JSAPI (mini program) → /v3/pay/transactions/jsapi
    //   'h5'     = mobile H5            → /v3/pay/transactions/h5
    'trade_type' => env('WECHATPAY_TRADE_TYPE', 'native'),

    'return_url' => env('WECHATPAY_RETURN_URL'),
    'notify_url' => env('WECHATPAY_NOTIFY_URL'),
];
