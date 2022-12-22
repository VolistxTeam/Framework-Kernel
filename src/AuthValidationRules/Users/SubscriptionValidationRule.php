<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class SubscriptionValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $user_id = PersonalTokens::getToken()->user_id;

        $active_subscription = Subscriptions::ProcessUserActiveSubscriptionsStatus($user_id);
        if ($active_subscription) {
            Subscriptions::setSubscription($active_subscription);
            Plans::setPlan($active_subscription->plan);
            return true;
        }

        $inactive_subscription = Subscriptions::ProcessUserInactiveSubscriptionsStatus($user_id);
        if ($inactive_subscription) {
            Subscriptions::setSubscription($inactive_subscription);
            Plans::setPlan($inactive_subscription->plan);
            return true;
        }

        return [
            'message' => Messages::E403(trans('volistx::subscription.expired')),
            'code' => 403,
        ];
    }
}
