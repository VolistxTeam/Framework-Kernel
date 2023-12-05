<?php

namespace Volistx\FrameworkKernel\Tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Volistx\FrameworkKernel\Database\Factories\AccessTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\SubscriptionFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Database\Factories\UserLogFactory;
use Volistx\FrameworkKernel\DataTransferObjects\SubscriptionDTO;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function AuthorizeCreateSubscriptionPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();

        $this->TestPermissions($token, $key, 'postJson', "/sys-bin/admin/users/$user->id/subscriptions", [
            'subscriptions:*' => 201,
            '' => 401,
            'subscriptions:create' => 201,
        ], [
            'plan_id' => $plan->id,
            'activated_at' => Carbon::now()->toString(),
            'expires_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function CreateSubscription(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->postJson("/sys-bin/admin/users/$user->id/subscriptions", [
            'plan_id' => $plan->id,
            'activated_at' => Carbon::now()->toString(),
            'expires_at' => null,
        ]);

        $response->assertStatus(201);
    }

    /**
     * @test
     */
    public function AuthorizeMutateSubscriptionPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $this->TestPermissions($token, $key, 'postJson', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id", [
            'subscriptions:*' => 200,
            '' => 401,
            'subscriptions:mutate' => 200,
        ], [
            'expires_at' => null
        ]);
    }

    /**
     * @test
     */
    public function MutateSubscription(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->postJson("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id", [
            'expires_at' => Carbon::now()
        ]);

        $response->assertStatus(200);
        self::assertSame(2, Subscription::query()->count());
        self::assertSame(SubscriptionStatus::DEACTIVATED, Subscription::query()->get()[0]->status);
        self::assertSame(SubscriptionStatus::ACTIVE, Subscription::query()->get()[1]->status);
    }

    /**
     * @test
     */
    public function AuthorizeDeleteSubscriptionPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $this->TestPermissions($token, $key, 'delete', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id", [
            '' => 401,
            'subscriptions:delete' => 204,
        ]);
    }

    /**
     * @test
     */
    public function DeleteSubscription(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->delete("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id");

        $response->assertStatus(204);
        self::assertSame(0, Subscription::query()->count());
    }

    /**
     * @test
     */
    public function AuthorizeCancelSubscriptionPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $this->TestPermissions($token, $key, 'patchJson', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/cancel", [
            'subscriptions:*' => 200,
            '' => 401,
            'subscriptions:cancel' => 200,
        ], [
            'cancels_at' => Carbon::now()->addDay()->toString(),
        ]);
    }

    /**
     * @test
     */
    public function CancelSubscription(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);
        $cancels_at_date = Carbon::now()->addDay()->toString();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->patchJson("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/cancel", [
            'cancels_at' => $cancels_at_date
        ]);

        $response->assertStatus(200);
        self::assertSame($cancels_at_date, Subscription::query()->first()->cancels_at->toString());
    }

    /**
     * @test
     */
    public function AuthorizeRevertCancelSubscriptionPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id, [
            'cancels_at' => Carbon::now()->toString()
        ]);

        $this->TestPermissions($token, $key, 'patchJson', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/revert-cancel", [
            '' => 401,
            'subscriptions:revert-cancel' => 200,
        ]);
    }

    /**
     * @test
     */
    public function RevertCancelSubscription(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id, [
            'cancels_at' => Carbon::now()->toString()
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->patchJson("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/revert-cancel");

        $response->assertStatus(200);
        self::assertNull(Subscription::query()->first()->cancels_at);
    }

    /**
     * @test
     */
    public function AuthorizeGetSubscriptionPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $this->TestPermissions($token, $key, 'get', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id", [
            'subscriptions:*' => 200,
            '' => 401,
            'subscriptions:view' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSubscription(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->get("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id");

        $response->assertStatus(200)
            ->assertJson(SubscriptionDTO::fromModel(Subscription::query()->first())->GetDTO());
    }

    /**
     * @test
     */
    public function AuthorizeGetSubscriptionsPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();

        $this->TestPermissions($token, $key, 'get', "/sys-bin/admin/users/$user->id/subscriptions", [
            'subscriptions:*' => 200,
            '' => 401,
            'subscriptions:view-all' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSubscriptions(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscriptions = SubscriptionFactory::new()->count(3)->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->get("/sys-bin/admin/users/$user->id/subscriptions");

        $response->assertStatus(200);
        self::assertCount(3, json_decode($response->getContent())->items);
    }

    /**
     * @test
     */
    public function AuthorizeGetSubscriptionLogsPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $this->TestPermissions($token, $key, 'get', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/logs", [
            'subscriptions:*' => 200,
            '' => 401,
            'subscriptions:logs' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSubscriptionLogs(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);
        UserLogFactory::new()->count(5)->create(['subscription_id' => $subscription->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->get("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/logs");

        $response->assertStatus(200);
        self::assertCount(5, json_decode($response->getContent())->items);
    }

    /**
     * @test
     */
    public function AuthorizeGetSubscriptionUsagesPermissions()
    {
        $key = Str::random(64);
        $token = $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);

        $this->TestPermissions($token, $key, 'get', "/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/usages", [
            'subscriptions:*' => 200,
            '' => 401,
            'subscriptions:stats' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSubscriptionUsages(): void
    {
        $key = Str::random(64);
        $this->generateAccessToken($key);
        $user = $this->GenerateUser();
        $plan = $this->GeneratePlan();
        $subscription = $this->GenerateSubscription($user->id, $plan->id);
        UserLogFactory::new()->count(5)->create(['subscription_id' => $subscription->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->get("/sys-bin/admin/users/$user->id/subscriptions/$subscription->id/usages");

        $response->assertStatus(200);
        self::assertSame(5, json_decode($response->getContent())->usages->current);
    }


    private function GenerateAccessToken(string $key): Collection|Model
    {
        $salt = Str::random(16);

        return AccessTokenFactory::new()
            ->create(['key' => substr($key, 0, 32),
                'secret' => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
                'secret_salt' => $salt,
                'permissions' => ['subscriptions:*']
            ]);
    }

    private function GenerateUser(): Collection|Model
    {
        return UserFactory::new()->create();
    }

    private function GeneratePlan(): Collection|Model
    {
        return PlanFactory::new()->create();
    }

    private function GenerateSubscription(string $user_id, string $plan_id, $data = []): Collection|Model
    {
        return SubscriptionFactory::new()->create(array_merge([
            'user_id' => $user_id,
            'plan_id' => $plan_id
        ], $data));
    }
}
