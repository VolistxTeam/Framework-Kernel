<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Facades\Messages;

class RateLimitValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = $this->inputs['token'];
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
                    'message' => Messages::E429(),
                    'code'    => 429,
                ];
            }
        }

        return true;
    }
}
