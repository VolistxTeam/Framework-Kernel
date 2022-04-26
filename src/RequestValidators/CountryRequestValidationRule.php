<?php

namespace Volistx\FrameworkKernel\RequestValidators;

use Illuminate\Contracts\Validation\Rule;
use MenaraSolutions\Geographer\Earth;

class CountryRequestValidationRule implements Rule
{
    private array $countries;

    public function __construct()
    {
        $this->countries = (new Earth())->getCountries()->useShortNames()->toArray();
    }

    public function passes($attribute, $value): bool
    {
        foreach ($value as $country) {
            if (!in_array(strtoupper($country), $this->countries)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'One or more country short name is invalid';
    }
}
