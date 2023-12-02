<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Volistx\FrameworkKernel\Database\Factories\AccessTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\PlanFactory;
use Volistx\FrameworkKernel\Database\Factories\SubscriptionFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Database\Factories\UserLogFactory;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\UserLog;
use Volistx\FrameworkKernel\Tests\TestCase;

class UserLogControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function AuthorizeGetLogPermissions()
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key, 1);
        $log = UserLog::query()->first();

        $this->TestPermissions($token, $key, 'get', "/sys-bin/admin/user-logs/$log->id", [
            'user-logs:*'    => 200,
            ''               => 401,
            'user-logs:view' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetLog(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key, 1);
        $log = UserLog::query()->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$key,
        ])->get("/sys-bin/admin/user-logs/$log->id");

        $response->assertStatus(200);
        self::assertSame($log->id, json_decode($response->getContent())->id);
    }

    /**
     * @test
     */
    public function AuthorizeGetLogsPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key, 5);

        $this->TestPermissions($token, $key, 'get', '/sys-bin/admin/user-logs', [
            'user-logs:*'        => 200,
            ''                   => 401,
            'user-logs:view-all' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetLogsWithDefaultPagination(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key, 50);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$key,
        ])->get('/sys-bin/admin/user-logs');

        $response->assertStatus(200);
        self::assertCount(50, json_decode($response->getContent())->items);
    }

    /**
     * @test
     */
    public function GetLogsWithCustomPagination(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key, 50);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$key,
        ])->get('/sys-bin/admin/user-logs?limit=1');

        $response->assertStatus(200);
        self::assertCount(1, json_decode($response->getContent())->items);
    }

    private function GenerateAccessToken(string $key, int $logsCount): Collection|Model
    {
        $salt = Str::random(16);
        $token = AccessTokenFactory::new()
            ->create(['key'   => substr($key, 0, 32),
                'secret'      => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
                'secret_salt' => $salt,
                'permissions' => ['user-logs:*'], ]);

        $user = UserFactory::new()->create();

        $plan = PlanFactory::new()->create();

        $subscription = SubscriptionFactory::new()->create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
        ]);

        UserLogFactory::new()->count($logsCount)->create([
            'subscription_id' => $subscription->id,
        ]);

        return $token;
    }
}
