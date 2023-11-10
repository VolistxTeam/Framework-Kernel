<?php
namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class PersonalTokenExpiryValidationRule extends ValidationRuleBase
{
    /**
     * Validates the expiry of the personal token.
     *
     * @return bool|array Returns true if the token is not expired, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        // If the token has an expiry date and the current time is greater than the expiry date, deny access
        if ($token->expires_at && Carbon::now()->greaterThan(Carbon::createFromTimeString($token->expires_at))) {
            return [
                'message' => Messages::E403(trans('volistx::token.expired')),
                'code'    => 403,
            ];
        }

        // Allow access if the token is not expired
        return true;
    }
}