<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Users\RequestsCountValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\SubscriptionFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Database\Factories\UserLogFactory;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class RequestsCountValidationRuleTest extends TestCase
{
    public function testAccessAllowedWhenRequestsCountWithinLimit()
    {
        $user = $this->GenerateUser(true);
        $plan = $this->GeneratePlan(['requests' => 500, 'duration' => 30]);
        $subscription = $this->GenerateSubscription($user->id, $plan->id, SubscriptionStatus::ACTIVE);

        Subscriptions::shouldReceive('getSubscription')->andReturn($subscription);
        Plans::shouldReceive('getPlan')->andReturn($plan);

        $requestMock = $this->createMock(Request::class);
        $requestsCountValidationRule = new RequestsCountValidationRule($requestMock);
        $result = $requestsCountValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessAllowedWhenPlanHasNoRequests()
    {
        $user = $this->GenerateUser(true);
        $plan = $this->GeneratePlan(['duration' => 30]);
        $subscription = $this->GenerateSubscription($user->id, $plan->id, SubscriptionStatus::ACTIVE);

        Subscriptions::shouldReceive('getSubscription')->andReturn($subscription);
        Plans::shouldReceive('getPlan')->andReturn($plan);

        $requestMock = $this->createMock(Request::class);
        $requestsCountValidationRule = new RequestsCountValidationRule($requestMock);
        $result = $requestsCountValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessNotAllowedWhenRequestsCountExceedsLimit()
    {
        $user = $this->GenerateUser(true);
        $plan = $this->GeneratePlan(['requests' => 500, 'duration' => 30]);
        $subscription = $this->GenerateSubscription($user->id, $plan->id, SubscriptionStatus::ACTIVE);
        $this->GenerateLogs($subscription->id, 501);
        Subscriptions::shouldReceive('getSubscription')->andReturn($subscription);
        Plans::shouldReceive('getPlan')->andReturn($plan);

        $requestMock = $this->createMock(Request::class);
        $requestsCountValidationRule = new RequestsCountValidationRule($requestMock);
        $result = $requestsCountValidationRule->validate();

        $this->assertTrue($result);
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

    private function GenerateLogs(string $subscriptionId, int $count): Collection|Model
    {
        return UserLogFactory::new()->count($count)->create(['subscription_id' => $subscriptionId]);
    }
}
