<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class IsActiveUserValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $user = Subscriptions::getSubscription()->user;

        if ($user->isActive === false) {
            return [
                'message' => Messages::E403(trans('volistx::user:inactive_user')),
                'code'    => 403,
            ];
        }

        return true;
    }
}
