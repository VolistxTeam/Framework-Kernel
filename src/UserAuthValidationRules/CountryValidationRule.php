<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\GeoLocations;
use Volistx\FrameworkKernel\Facades\Messages;

class CountryValidationRule extends ValidationRuleBase
{
    public function Validate(): bool|array
    {
        $token = $this->inputs['token'];
        $request = $this->inputs['request'];

        if (AccessRule::from($token->country_rule) === AccessRule::NONE) {
            return true;
        }

        $country = GeoLocations::search($request->getClientIp())->country;

        if ((AccessRule::from($token->country_rule) === AccessRule::BLACKLIST && in_array($country, $token->country_range)) ||
            (AccessRule::from($token->country_rule) === AccessRule::WHITELIST && !in_array($country, $token->country_range))) {
            return [
                'message' => Messages::E403('Not allowed in your country'),
                'code' => 403,
            ];
        }

        return true;
    }
}
