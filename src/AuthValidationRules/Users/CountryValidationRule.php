<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class CountryValidationRule extends ValidationRuleBase
{
    /**
     * Validates the country based on the access rule defined in the personal token.
     *
     * @return bool|array Returns true if the country is allowed, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $token = PersonalTokens::getToken();

        // If the country rule is set to NONE, allow access
        if ($token->country_rule === AccessRule::NONE) {
            return true;
        }

        $geolocation = geoip($this->request->getClientIp());

        // If the geolocation is not available, deny access
        if ($geolocation->default) {
            return [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_country')),
                'code'    => 403,
            ];
        }

        $countryCode = $geolocation->iso_code;

        // If the country rule is set to BLACKLIST and the country code is in the range or If the country rule
        // is set to WHITELIST and the country code is not in the range, deny access
        if (($token->country_rule === AccessRule::BLACKLIST && in_array($countryCode, $token->country_range)) ||
            ($token->country_rule === AccessRule::WHITELIST && !in_array($countryCode, $token->country_range))) {
            return [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_country')),
                'code'    => 403,
            ];
        }

        // Allow access if none of the above conditions are met
        return true;
    }
}
