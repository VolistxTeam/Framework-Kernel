<?php
namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class SubscriptionValidationRule extends ValidationRuleBase
{
    /**
     * Validates the user's subscription status.
     *
     * @return bool|array Returns true if the user has an active subscription, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $userId = PersonalTokens::getToken()->user_id;

        // Process the user's active subscriptions status
        $activeSubscription = Subscriptions::ProcessUserActiveSubscriptionsStatus($userId);

        if ($activeSubscription) {
            Subscriptions::setSubscription($activeSubscription);
            Plans::setPlan($activeSubscription->plan);
            return true;
        }

        // Process the user's inactive subscriptions status
        $inactiveSubscription = Subscriptions::ProcessUserInactiveSubscriptionsStatus($userId);

        if ($inactiveSubscription) {
            Subscriptions::setSubscription($inactiveSubscription);
            Plans::setPlan($inactiveSubscription->plan);
            return true;
        }

        // If the user does not have an active or inactive subscription, deny access
        return [
            'message' => Messages::E403(trans('volistx::subscription.expired')),
            'code'    => 403,
        ];
    }
}