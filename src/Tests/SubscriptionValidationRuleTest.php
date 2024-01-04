<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Users\SubscriptionValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\SubscriptionFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class SubscriptionValidationRuleTest extends TestCase
{
    public function testAccessAllowedWithActiveSubscription()
    {
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan(['requests' => 500]);
        $personalToken = $this->generatePersonalToken($user->id, []);
        $subscription = $this->GenerateSubscription(
            $user->id,
            [
                'status'  => SubscriptionStatus::ACTIVE,
                'plan_id' => $plan->id,
            ]
        );
        PersonalTokens::shouldReceive('getToken')->andReturn($personalToken);

        Subscriptions::shouldReceive('ProcessUserActiveSubscriptionsStatus')->andReturn($subscription);

        Subscriptions::shouldReceive('setSubscription')->once();
        Plans::shouldReceive('setPlan')->once();

        $requestMock = $this->createMock(Request::class);
        $subscriptionValidationRule = new SubscriptionValidationRule($requestMock);
        $result = $subscriptionValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessAllowedWithInactiveSubscription()
    {
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan(['requests' => 500]);
        $personalToken = $this->generatePersonalToken($user->id, []);
        $subscription = $this->GenerateSubscription(
            $user->id,
            [
                'status'  => SubscriptionStatus::INACTIVE,
                'plan_id' => $plan->id,
            ]
        );
        PersonalTokens::shouldReceive('getToken')->andReturn($personalToken);

        Subscriptions::shouldReceive('ProcessUserActiveSubscriptionsStatus')->andReturn(null);

        Subscriptions::shouldReceive('ProcessUserInactiveSubscriptionsStatus')->andReturn($subscription);

        Subscriptions::shouldReceive('setSubscription')->once();
        Plans::shouldReceive('setPlan')->once();

        $requestMock = $this->createMock(Request::class);
        $subscriptionValidationRule = new SubscriptionValidationRule($requestMock);
        $result = $subscriptionValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessDeniedWithoutActiveOrInactiveSubscription()
    {
        $user = $this->GenerateUser();
        $personalToken = $this->generatePersonalToken($user->id, []);

        PersonalTokens::shouldReceive('getToken')->andReturn($personalToken);

        Subscriptions::shouldReceive('ProcessUserActiveSubscriptionsStatus')->andReturn(null);
        Subscriptions::shouldReceive('ProcessUserInactiveSubscriptionsStatus')->andReturn(null);

        $requestMock = $this->createMock(Request::class);
        $subscriptionValidationRule = new SubscriptionValidationRule($requestMock);
        $result = $subscriptionValidationRule->validate();

        $this->assertEquals(
            [
                'message' => Messages::E403(trans('volistx::subscription.expired')),
                'code'    => 403,
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

    private function generatePersonalToken(string $user_id, array $inputs): Collection|Model
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

    private function GenerateSubscription(string $user_id, array $inputs): Collection|Model
    {
        return SubscriptionFactory::new()->create(
            array_merge(
            [
                'user_id' => $user_id,
            ],
            $inputs
        )
        );
    }
}
