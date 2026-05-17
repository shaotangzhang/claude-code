<?php

declare(strict_types=1);

return [
    // Twilio-compatible by default: POST <api_base>/Accounts/<sid>/Messages.json
    // Body params: From, To, Body. Basic auth: sid / token.
    'api_base'  => env('ACME_SMS_API_BASE', 'https://api.twilio.com/2010-04-01'),
    'sid'       => env('ACME_SMS_SID'),
    'token'     => env('ACME_SMS_TOKEN'),
    'from'      => env('ACME_SMS_FROM'),

    // Soft mode: if true and creds are missing, log instead of throwing.
    'log_when_unconfigured' => true,
];
