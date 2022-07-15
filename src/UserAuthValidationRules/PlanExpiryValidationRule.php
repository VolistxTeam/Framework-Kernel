<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Facades\Messages;

class PlanExpiryValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $subscription = $this->inputs['token']->subscription()->first();

        if (Carbon::now()->greaterThan(Carbon::createFromTimeString($subscription->plan_expires_at))) {
            return [
                'message' => Messages::E403('Your subscription has been expired. Please renew if you want to continue using this service.'),
                'code'    => 403,
            ];
        }

        return true;
    }
}
