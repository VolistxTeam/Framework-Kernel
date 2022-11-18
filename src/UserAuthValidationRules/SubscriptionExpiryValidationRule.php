<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;

class SubscriptionExpiryValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $subscription = $this->inputs['subscription'];

        if ($subscription->status != SubscriptionStatus::ACTIVE) {
            return [
                'message' => Messages::E403('Your subscription is not active.'),
                'code' => 403,
            ];
        }

        if (!empty($subscription->plan_expires_at)) {
            if (Carbon::now()->greaterThan(Carbon::createFromTimeString($subscription->plan_expires_at))) {
                return [
                    'message' => Messages::E403('Your subscription has been expired. Please renew if you want to continue using this service.'),
                    'code' => 403,
                ];
            }
        }

        return true;
    }
}
