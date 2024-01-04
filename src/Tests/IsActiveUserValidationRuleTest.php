<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IsActiveUserValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\SubscriptionFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class IsActiveUserValidationRuleTest extends TestCase
{
    public function testAccessAllowedWhenUserIsActive()
    {
        $user = $this->GenerateUser(true);
        $plan = $this->GeneratePlan(['requests' => 500]);
        $subscription = $this->GenerateSubscription($user->id, $plan->id, SubscriptionStatus::ACTIVE);
        Subscriptions::shouldReceive('getSubscription')->andReturn($subscription);

        $requestMock = $this->createMock(Request::class);
        $isActiveUserValidationRule = new IsActiveUserValidationRule($requestMock);

        $result = $isActiveUserValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessDeniedWhenUserIsInactive()
    {
        $user = $this->GenerateUser(false);
        $plan = $this->GeneratePlan(['requests' => 500]);
        $subscription = $this->GenerateSubscription($user->id, $plan->id, SubscriptionStatus::ACTIVE);
        Subscriptions::shouldReceive('getSubscription')->andReturn($subscription);

        $requestMock = $this->createMock(Request::class);
        $isActiveUserValidationRule = new IsActiveUserValidationRule($requestMock);

        $result = $isActiveUserValidationRule->validate();

        $this->assertEquals(
            [
                'message' => Messages::E403(trans('volistx::user:inactive_user')),
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
            'status'  => $status,
        ]);
    }

    private function GenerateUser(bool $is_active): Collection|Model
    {
        return UserFactory::new()->create([
            'is_active' => $is_active,
        ]);
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
