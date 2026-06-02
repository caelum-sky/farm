<?php

return [
    'api_token_ttl_minutes' => (int) env('API_TOKEN_TTL_MINUTES', 1440),

    'webhooks' => [
        'require_signatures' => (bool) env('WEBHOOK_REQUIRE_SIGNATURES', env('APP_ENV') === 'production'),
        'secrets' => [
            'stripe' => env('STRIPE_WEBHOOK_SECRET'),
            'twilio' => env('TWILIO_WEBHOOK_SECRET'),
            'courier' => env('COURIER_WEBHOOK_SECRET'),
            'kyc' => env('KYC_WEBHOOK_SECRET'),
            'manual' => env('MANUAL_WEBHOOK_SECRET'),
        ],
    ],

    'trusted_image_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('TRUSTED_IMAGE_HOSTS', ''))
    ))),
];
