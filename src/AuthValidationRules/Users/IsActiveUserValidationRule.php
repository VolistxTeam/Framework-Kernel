<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class IsActiveUserValidationRule extends ValidationRuleBase
{
    /**
     * Validates if the user is active.
     *
     * @return bool|array Returns true if the user is active, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $user = Subscriptions::getSubscription()->user;

        // If the user is inactive, deny access
        if (!($user->is_active)) {
            return [
                'message' => Messages::E403(trans('volistx::user:inactive_user')),
                'code'    => 403,
            ];
        }

        // Allow access if the user is active
        return true;
    }
}
