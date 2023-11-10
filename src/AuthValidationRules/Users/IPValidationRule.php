<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Wikimedia\IPSet;

class IPValidationRule extends ValidationRuleBase
{
    /**
     * Validates the IP address based on the access rule defined in the personal token.
     *
     * @return bool|array Returns true if the IP is allowed, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        // If the IP rule is set to NONE, allow access
        if ($token->ip_rule === AccessRule::NONE) {
            return true;
        }

        $ipSet = new IPSet($token->ip_range);
        $clientIp = $this->request->getClientIp();

        // If the IP rule is set to BLACKLIST and the client IP matches the IP range or If the IP rule is
        // set to WHITELIST and the client IP does not match the IP range, deny access
        if ($token->ip_rule === AccessRule::BLACKLIST && $ipSet->match($clientIp) ||
            ($token->ip_rule === AccessRule::WHITELIST && !$ipSet->match($clientIp))) {
            return [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_ip')),
                'code' => 403,
            ];
        }

        // Allow access if none of the above conditions are met
        return true;
    }
}
