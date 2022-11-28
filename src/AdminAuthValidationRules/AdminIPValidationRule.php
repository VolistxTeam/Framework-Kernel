<?php

namespace Volistx\FrameworkKernel\AdminAuthValidationRules;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\UserAuthValidationRules\ValidationRuleBase;
use Wikimedia\IPSet;

class AdminIPValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = AccessTokens::getToken();

        if ($token->ip_rule === AccessRule::NONE) {
            return true;
        }

        $ipSet = new IPSet($token->ip_range);

        if ($token->ip_rule === AccessRule::BLACKLIST && $ipSet->match($this->request->getClientIp()) ||
            ($token->ip_rule === AccessRule::WHITELIST && !$ipSet->match($this->request->getClientIp()))) {
            return [
                'message' => Messages::E403('This service is not allowed to access from your IP address.'),
                'code'    => 403,
            ];
        }

        return true;
    }
}
