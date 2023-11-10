<?php
namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;

class SubscriptionRateLimitValidationRule extends ValidationRuleBase
{
    /**
     * Validates the rate limit for subscription based on the rate limit mode defined in the personal token.
     *
     * @return bool|array Returns true if the rate limit is not exceeded, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        // If the rate limit mode is not set to SUBSCRIPTION, allow access
        if ($token->rate_limit_mode !== RateLimitMode::SUBSCRIPTION) {
            return true;
        }

        $plan = Plans::getPlan();

        // If the plan has a rate limit defined
        if (isset($plan['data']['rate_limit'])) {
            // Attempt to execute the rate limiter for the subscription ID
            $executed = RateLimiter::attempt(
                $token->subscription_id,
                $plan['data']['rate_limit'],
                function () {
                    // Empty function, no action needed
                }
            );

            // If the rate limit is exceeded, deny access
            if (!$executed) {
                return [
                    'message' => Messages::E429(),
                    'code'    => 429,
                ];
            }
        }

        // Allow access if none of the above conditions are met
        return true;
    }
}