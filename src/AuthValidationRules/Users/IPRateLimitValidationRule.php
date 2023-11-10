<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;

class IPRateLimitValidationRule extends ValidationRuleBase
{
    /**
     * Validates the IP rate limit based on the rate limit mode defined in the personal token.
     *
     * @return bool|array Returns true if the rate limit is not exceeded, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        // If the rate limit mode is not set to IP, allow access
        if ($token->rate_limit_mode !== RateLimitMode::IP) {
            return true;
        }

        $plan = Plans::getPlan();

        // If the plan has a rate limit defined
        if (isset($plan['data']['rate_limit'])) {
            // Attempt to execute the rate limiter for the client IP
            $executed = RateLimiter::attempt(
                $this->request->getClientIp(),
                $plan['data']['rate_limit'],
                function () {
                    // Empty function, no action needed
                }
            );

            // If the rate limit is exceeded, deny access
            if (!$executed) {
                return [
                    'message' => Messages::E429(),
                    'code' => 429,
                ];
            }
        }

        // Allow access if none of the above conditions are met
        return true;
    }
}