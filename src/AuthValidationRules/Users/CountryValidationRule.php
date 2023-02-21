<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class CountryValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        if ($token->country_rule === AccessRule::NONE) {
            return true;
        }

        $geolocation = geoip($this->request->getClientIp());

        if ($geolocation->default) {
            return [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_country')),
                'code'    => 403,
            ];
        }

        $code = $geolocation->iso_code;

        if (($token->country_rule === AccessRule::BLACKLIST && in_array($code, $token->country_range)) ||
            ($token->country_rule === AccessRule::WHITELIST && !in_array($code, $token->country_range))) {
            return [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_country')),
                'code'    => 403,
            ];
        }

        return true;
    }
}
