<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;

class SubscriptionRateLimitValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = $this->inputs['token'];

        if ($token->rate_limit_mode !== RateLimitMode::SUBSCRIPTION) {
            return true;
        }

        $plan = $this->inputs['plan'];

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
