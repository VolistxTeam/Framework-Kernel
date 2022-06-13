<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Messages;
use Wikimedia\IPSet;

class IPValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = $this->inputs['token'];
        $request = $this->inputs['request'];

        if ($token->ip_rule === AccessRule::NONE) {
            return true;
        }

        $ipSet = new IPSet($token->ip_range);

        if ($token->ip_rule === AccessRule::BLACKLIST && $ipSet->match($request->getClientIp()) ||
            ($token->ip_rule === AccessRule::WHITELIST && !$ipSet->match($request->getClientIp()))) {
            return [
                'message' => Messages::E403('This service is not allowed to access from your IP address.'),
                'code'    => 403,
            ];
        }

        return true;
    }
}
