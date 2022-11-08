<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class PlanExpiryValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $subscription = $this->inputs['token']->subscription()->first();

        if ($subscription->plan_cancels_at && Carbon::now()->gte($subscription->plan_cancels_at)) {
            if (config('volistx.fallback_plan.id') !== null) {
                $repo = new SubscriptionRepository();
                $repo->SwitchToFreePlan($subscription->id);

                return [
                    'message' => Messages::E403('Your plan has been cancelled and downgraded to free plan. Please subscribe to a new plan if you want to continue using this service.'),
                    'code'    => 403,
                ];
            } else {
                return [
                    'message' => Messages::E403('Your plan has been cancelled. Please subscribe to a new plan if you want to continue using this service.'),
                    'code'    => 403,
                ];
            }
        }

        if ($subscription->plan_expires_at != null) {
            if (Carbon::now()->greaterThan(Carbon::createFromTimeString($subscription->plan_expires_at))) {
                return [
                    'message' => Messages::E403('Your subscription has been expired. Please renew if you want to continue using this service.'),
                    'code'    => 403,
                ];
            }
        }

        return true;
    }
}
