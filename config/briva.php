<?php

declare(strict_types=1);

return [
    'base_url' => env('BRI_BASE_URL', 'https://sandbox.partner.api.bri.co.id'),
    'client_id' => env('BRI_CLIENT_ID', ''),
    'client_secret' => env('BRI_CLIENT_SECRET', ''),
    'private_key_pem' => env('BRI_PRIVATE_KEY_PEM', ''),
    'public_key_pem' => env('BRI_PUBLIC_KEY_PEM', ''),
    'channel_id' => env('BRI_CHANNEL_ID', ''),
    'partner_id' => env('BRI_PARTNER_ID', ''),
    'partner_service_id' => env('BRI_PARTNER_SERVICE_ID', ''),

    'wsdl' => [
        'endpoint' => env('BRI_WSDL_ENDPOINT', 'https://h2htest.uin-malang.ac.id/server.php?wsdl'),
        'inquiry_method' => env('BRI_WSDL_INQUIRY_METHOD', 'inquiry'),
        'payment_method' => env('BRI_WSDL_PAYMENT_METHOD', 'payment'),
    ],
    'inquiry_resolver' => \RahmatNurjaman99\BrivaOnline\Resolvers\WsdlInquiryResolver::class,
    'payment_resolver' => \RahmatNurjaman99\BrivaOnline\Resolvers\WsdlPaymentResolver::class,

    'token_ttl_seconds' => (int) env('BRI_TOKEN_TTL_SECONDS', 3600),

    'client_public_keys_json' => env('CLIENT_PUBLIC_KEYS_JSON', ''),
    'client_public_key_id' => env('CLIENT_PUBLIC_KEY_ID', ''),
    'client_public_key_pem' => env('CLIENT_PUBLIC_KEY_PEM', ''),

    'client_secrets_json' => env('CLIENT_SECRETS_JSON', ''),
    'client_secret_id' => env('CLIENT_SECRET_ID', ''),
    'client_secret' => env('CLIENT_SECRET', ''),

    'routes' => [
        'enabled' => env('BRIVA_ROUTES_ENABLED', true),
        'prefix' => env('BRIVA_ROUTES_PREFIX', ''),
    ],
];
