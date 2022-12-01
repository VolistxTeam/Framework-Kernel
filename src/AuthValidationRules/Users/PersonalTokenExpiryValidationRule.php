<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class PersonalTokenExpiryValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        if ($token->expires_at && Carbon::now()->greaterThan(Carbon::createFromTimeString($token->expires_at))) {
            return [
                'message' => Messages::E403('Your token has been expired. Please generate a new token if you want to continue using this service.'),
                'code'    => 403,
            ];
        }

        return true;
    }
}
