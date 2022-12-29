<?php

namespace Volistx\FrameworkKernel\Rules;

use Illuminate\Contracts\Validation\Rule;
use MenaraSolutions\Geographer\Earth;

class CountryRequest implements Rule
{
    private array $countries;

    public function __construct()
    {
        $this->countries = array_column((new Earth())->getCountries()->useShortNames()->toArray(), 'isoCode');
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
        return __('volistx::country_range.*.required_if');
    }
}
