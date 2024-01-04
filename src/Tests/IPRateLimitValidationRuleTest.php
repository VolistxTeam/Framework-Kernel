<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IPRateLimitValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;

class IPRateLimitValidationRuleTest extends TestCase
{
    public function testAccessAllowedWhenRateLimitModeIsSubscription()
    {
        // Set rate limit mode to something other than IP
        $this->GeneratePlan(['requests' => 500]);
        $user = $this->GenerateUser();
        $token = $this->generatePersonalToken($user->id, ['rate_limit_mode' => RateLimitMode::SUBSCRIPTION]);
        PersonalTokens::shouldReceive('getToken')->andReturn($token);

        $request = $this->createMock(Request::class);
        $ipRateLimitValidationRule = new IPRateLimitValidationRule($request);

        $result = $ipRateLimitValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessAllowedWhenRateLimitNotExceeded()
    {
        $plan = $this->GeneratePlan(['requests' => 500]);
        $user = $this->GenerateUser();
        $token = $this->generatePersonalToken($user->id, ['rate_limit_mode' => RateLimitMode::IP]);
        PersonalTokens::shouldReceive('getToken')->andReturn($token);

        Plans::shouldReceive('getPlan')->andReturn($plan);

        // Mock the RateLimiter to always return success
        RateLimiter::shouldReceive('attempt')->andReturn(true);

        $requestMock = $this->createMock(Request::class);
        $ipRateLimitValidationRule = new IPRateLimitValidationRule($requestMock);

        $result = $ipRateLimitValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessDeniedWhenRateLimitExceeded()
    {
        // Set rate limit mode to IP
        $plan = $this->GeneratePlan(['requests' => 500, 'rate_limit' => 1]);
        $user = $this->GenerateUser();
        $token = $this->generatePersonalToken($user->id, ['rate_limit_mode' => RateLimitMode::IP]);
        PersonalTokens::shouldReceive('getToken')->andReturn($token);

        Plans::shouldReceive('getPlan')->andReturn($plan);

        // Mock the RateLimiter to always return failure
        RateLimiter::shouldReceive('attempt')->andReturn(false);

        $requestMock = $this->createMock(Request::class);
        $ipRateLimitValidationRule = new IPRateLimitValidationRule($requestMock);

        $result = $ipRateLimitValidationRule->validate();

        $this->assertEquals(
            [
                'message' => Messages::E429(),
                'code'    => 429,
            ],
            $result
        );
    }

    private function GenerateUser(): Collection|Model
    {
        return UserFactory::new()->create();
    }

    private function GeneratePlan(array $data): Collection|Model
    {
        return PlanFactory::new()->create(['data' => $data]);
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
