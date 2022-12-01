<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;

class IPRateLimitValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        if ($token->rate_limit_mode !== RateLimitMode::IP) {
            return true;
        }

        $plan = Plans::getPlan();

        if (isset($plan['data']['rate_limit'])) {
            $executed = RateLimiter::attempt(
                $this->request->getClientIp(),
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
