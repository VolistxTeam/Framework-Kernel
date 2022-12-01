<?php

return [
    'firewall' => [
        'blacklist' => [

        ],
    ],
    'logging' => [
        'adminLogMode'      => env('LOG_AUTH_ADMIN_CHANNEL', 'local'),
        'adminLogHttpUrl'   => env('LOG_AUTH_ADMIN_HTTP_URL'),
        'adminLogHttpToken' => env('LOG_AUTH_ADMIN_HTTP_TOKEN'),
        'userLogMode'       => env('LOG_AUTH_USER_CHANNEL', 'local'),
        'userLogHttpUrl'    => env('LOG_AUTH_USER_HTTP_URL'),
        'userLogHttpToken'  => env('LOG_AUTH_USER_HTTP_TOKEN'),
    ],
    'geolocation' => [
        'token'             => env('GEOPOINT_API_KEY'),
        'secure'          => env('GEOPOINT_API_SECURE', true),
    ],
    'validators' => [
        \Volistx\FrameworkKernel\AuthValidationRules\Users\SubscriptionValidationRule::class, //must always be #1 in order as it sets the subscription and the plan for current request
        \Volistx\FrameworkKernel\AuthValidationRules\Users\SubscriptionRateLimitValidationRule::class,
        \Volistx\FrameworkKernel\AuthValidationRules\Users\PersonalTokenExpiryValidationRule::class,
        \Volistx\FrameworkKernel\AuthValidationRules\Users\IPValidationRule::class,
        \Volistx\FrameworkKernel\AuthValidationRules\Users\CountryValidationRule::class,
        \Volistx\FrameworkKernel\AuthValidationRules\Users\RequestsCountValidationRule::class,
        \Volistx\FrameworkKernel\AuthValidationRules\Users\IPRateLimitValidationRule::class,
    ],
    'services_permissions' => [
        '*',
    ],
    'fallback_plan' => [
        'id' => env('FALLBACK_PLAN_ID'),
    ],
];
