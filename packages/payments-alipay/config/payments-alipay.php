<?php

declare(strict_types=1);

return [
    'app_id'              => env('ALIPAY_APP_ID'),

    // PEM-formatted RSA keys. Either set the file path OR paste the
    // PEM body directly into env (line-breaks via \n preserved).
    'app_private_key'     => env('ALIPAY_APP_PRIVATE_KEY'),
    'alipay_public_key'   => env('ALIPAY_PUBLIC_KEY'),

    // 'live' → openapi.alipay.com ; 'sandbox' → openapi-sandbox.dl.alipaydev.com
    'mode' => env('ALIPAY_MODE', 'sandbox'),

    // Where Alipay redirects after the user approves.
    'return_url' => env('ALIPAY_RETURN_URL'),
    // Server-side async callback (the actual signed webhook).
    'notify_url' => env('ALIPAY_NOTIFY_URL'),

    'sign_type' => 'RSA2',
    'charset'   => 'utf-8',
    'version'   => '1.0',
];
