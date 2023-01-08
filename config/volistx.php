<?php

use Volistx\FrameworkKernel\AuthValidationRules\Users\CountryValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IPRateLimitValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IPValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IsActiveUserValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\PersonalTokenExpiryValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\RequestsCountValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\SubscriptionRateLimitValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\SubscriptionValidationRule;

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
        'base_url'          => env('GEOPOINT_API_BASEURL', 'geopoint.api.volistx.io'),
        'token'             => env('GEOPOINT_API_KEY'),
        'secure'            => env('GEOPOINT_API_SECURE', true),
    ],
    'validators' => [
        SubscriptionValidationRule::class, //must always be #1 in order as it sets the subscription and the plan for current request
        IsActiveUserValidationRule::class,
        SubscriptionRateLimitValidationRule::class,
        PersonalTokenExpiryValidationRule::class,
        IPValidationRule::class,
        CountryValidationRule::class,
        RequestsCountValidationRule::class,
        IPRateLimitValidationRule::class,
    ],
    'services_permissions' => [
        '*',
    ],
    'webhooks' => [
        'subscription' => [
            'expired' => [
                'url'   => env('SUBSCRIPTION_EXPIRED_WEBHOOK_URL'),
                'token' => env('SUBSCRIPTION_EXPIRED_WEBHOOK_TOKEN'),
            ],
            'cancelled' => [
                'url'   => env('SUBSCRIPTION_CANCELLED_WEBHOOK_URL'),
                'token' => env('SUBSCRIPTION_CANCELLED_WEBHOOK_TOKEN'),
            ],
            'expires_soon' => [
                'url'   => env('SUBSCRIPTION_EXPIRES_SOON_WEBHOOK_URL'),
                'token' => env('SUBSCRIPTION_EXPIRES_SOON_WEBHOOK_TOKEN'),
            ],
        ],
    ],
];
