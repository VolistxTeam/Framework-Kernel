<?php

namespace Volistx\FrameworkKernel\Tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IsActiveUserValidationRule;
use Volistx\FrameworkKernel\AuthValidationRules\Users\PersonalTokenExpiryValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\SubscriptionFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class PersonalTokenExpiryValidationRuleTest extends TestCase
{
    public function testAccessAllowedWhenTokenNotExpired()
    {
        $user = $this->GenerateUser(true);
        $personalToken = $this->GeneratePersonalToken($user->id, [
            'expires_at' => Carbon::now()->addHour()->toDateTimeString()
        ]);

        PersonalTokens::shouldReceive('getToken')->andReturn($personalToken);

        $requestMock = $this->createMock(Request::class);
        $expiryValidationRule = new PersonalTokenExpiryValidationRule($requestMock);

        $result = $expiryValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessAllowedWhenTokenHasNoExpiry()
    {
        $user = $this->GenerateUser(true);
        $personalToken = $this->GeneratePersonalToken($user->id, [
            'expires_at' => null
        ]);

        PersonalTokens::shouldReceive('getToken')->andReturn($personalToken);

        $requestMock = $this->createMock(Request::class);
        $expiryValidationRule = new PersonalTokenExpiryValidationRule($requestMock);

        $result = $expiryValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessDeniedWhenTokenExpired()
    {
        $user = $this->GenerateUser(true);
        $personalToken = $this->GeneratePersonalToken($user->id, [
            'expires_at' => Carbon::now()->subHour()->toDateTimeString()
        ]);

        PersonalTokens::shouldReceive('getToken')->andReturn($personalToken);

        $requestMock = $this->createMock(Request::class);
        $expiryValidationRule = new PersonalTokenExpiryValidationRule($requestMock);

        $result = $expiryValidationRule->validate();

        $this->assertEquals(
            [
                'message' => Messages::E403(trans('volistx::token.expired')),
                'code'    => 403,
            ],
            $result
        );
    }

    private function GenerateSubscription($user_id, $plan_id, SubscriptionStatus $status): Collection|Model
    {
        return SubscriptionFactory::new()->create([
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'status' => $status
        ]);
    }

    private function GenerateUser(bool $is_active): Collection|Model
    {
        return UserFactory::new()->create([
            'is_active' => $is_active
        ]);
    }

    private function GeneratePersonalToken(string $user_id, array $inputs): Collection|Model
    {
        return PersonalTokenFactory::new()->create(array_merge(
                [
                    'user_id' => $user_id
                ],
                $inputs)
        );
    }
}
