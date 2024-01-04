<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Users\CountryValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class CountryValidationRuleTest extends TestCase
{
    public function testAccessAllowedWhenCountryRuleIsNone()
    {
        $user = $this->GenerateUser();
        $personal_Token = $this->GeneratePersonalToken($user->id, [
            'country_rule' => AccessRule::NONE,
        ]);

        $requestMock = $this->createMock(Request::class);
        $countryValidationRule = new CountryValidationRule($requestMock);
        PersonalTokens::shouldReceive('getToken')->andReturn($personal_Token);

        $result = $countryValidationRule->validate();

        $this->assertTrue($result);
    }

    private function GenerateUser(): Collection|Model
    {
        return UserFactory::new()->create();
    }

    private function GeneratePersonalToken(string $user_id, array $inputs): Collection|Model
    {
        return PersonalTokenFactory::new()->create(
            array_merge(
            [
                'user_id' => $user_id,
            ],
            $inputs
        )
        );
    }
}
