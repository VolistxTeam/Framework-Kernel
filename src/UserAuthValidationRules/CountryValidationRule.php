<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\GeoLocation;
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

        $geolocation = GeoLocation::search($this->request->getClientIp());

        if (!$geolocation) {
            return [
                'message' => Messages::E403('This service is not allowed to access from your country.'),
                'code'    => 403,
            ];
        }

        if ($geolocation->bogon === true) {
            return true;
        }

        $code = $geolocation->country->code;

        if (($token->country_rule === AccessRule::BLACKLIST && in_array($code, $token->country_range)) ||
            ($token->country_rule === AccessRule::WHITELIST && !in_array($code, $token->country_range))) {
            return [
                'message' => Messages::E403('This service is not allowed to access from your country.'),
                'code'    => 403,
            ];
        }

        return true;
    }
}
