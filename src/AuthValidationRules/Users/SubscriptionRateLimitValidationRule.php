<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;

class SubscriptionRateLimitValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        if ($token->rate_limit_mode !== RateLimitMode::SUBSCRIPTION) {
            return true;
        }

        $plan = Plans::getPlan();

        if (isset($plan['data']['rate_limit'])) {
            $executed = RateLimiter::attempt(
                $token->subscription_id,
                $plan['data']['rate_limit'],
                function () {
                }
            );

            if (!$executed) {
                return [
                    'message' => Messages::E429('You have exceeded the rate limit.'),
                    'code'    => 429,
                ];
            }
        }

        return true;
    }
}
