<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;

class IPRateLimitValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = $this->inputs['token'];

        if ($token->rate_limit_mode !== RateLimitMode::IP) {
            return true;
        }

        $plan = $this->inputs['plan'];
        $request = $this->inputs['request'];

        if (isset($plan['data']['rate_limit'])) {
            $executed = RateLimiter::attempt(
                $request->getClientIp(),
                $plan['data']['rate_limit'],
                function () {
                }
            );

            if (!$executed) {
                return [
                    'message' => Messages::E429('You have exceeded the rate limit.'),
                    'code' => 429,
                ];
            }
        }

        return true;
    }
}
