<?php

namespace Volistx\FrameworkKernel\Tests;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\AccessToken;
use Volistx\FrameworkKernel\Models\PersonalToken;
use Volistx\FrameworkKernel\Models\Plan;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Models\UserLog;

class SubscriptionControllerTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function createApplication(): Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * @test
     */
    public function AuthorizeCreateSubPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);

        $this->TestPermissions($token, $key, 'POST', '/sys-bin/admin/subscriptions/', [
            'subscriptions:*'      => 201,
            'subscriptions:create' => 201,
            ''                     => 401,
        ], [
            'plan_id'           => Plan::query()->first()->id,
            'user_id'           => 1,
            'plan_activated_at' => Carbon::now(),
            'plan_expires_at'   => Carbon::now()->addHours(50),
        ]);
    }

    private function GenerateAccessToken(string $key): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $salt = Str::random(16);

        return AccessToken::factory()
            ->create(['key'   => substr($key, 0, 32),
                'secret'      => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
                'secret_salt' => $salt,
                'permissions' => ['subscriptions:*'], ]);
    }

    /**
     * @test
     *
     * @param int[] $permissions
     * @param (Carbon|int|mixed|null)[] $input
     *
     * @psalm-param array{'subscriptions:*'?: 200|201|204, 'subscriptions:create'?: 201, ''?: 401, 'subscriptions:update'?: 200, 'subscriptions:delete'?: 204, 'subscriptions:view'?: 200, 'subscriptions:view-all'?: 200, 'subscriptions:logs'?: 200} $permissions
     * @psalm-param array{plan_id?: mixed|null, user_id?: 1, plan_activated_at?: Carbon, plan_expires_at?: Carbon} $input
     */
    private function TestPermissions($token, string $key, string $verb, string $route, array $permissions, array $input = []): void
    {
        foreach ($permissions as $permissionName => $permissionResult) {
            $token->permissions = [$permissionName];
            $token->save();

            $request = $this->json($verb, $route, $input, [
                'Authorization' => "Bearer $key",
            ]);
            self::assertResponseStatus($permissionResult);
        }
    }

    /**
     * @test
     */
    public function CreateSub(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);

        $request = $this->json('POST', '/sys-bin/admin/subscriptions/', [
            'plan_id'           => Plan::query()->first()->id,
            'user_id'           => 1,
            'plan_activated_at' => Carbon::now(),
            'plan_expires_at'   => Carbon::now()->addHours(50),
        ], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(201);
        self::assertSame('1', json_decode($request->response->getContent())->user_id);
        self::assertSame(Plan::query()->first()->id, json_decode($request->response->getContent())->plan->id);
    }

    /**
     * @test
     */
    public function AuthorizeUpdateSubPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $this->TestPermissions(
            $token,
            $key,
            'PUT',
            "/sys-bin/admin/subscriptions/{$sub->id}",
            [
                'subscriptions:*'      => 200,
                'subscriptions:update' => 200,
                ''                     => 401,
            ],
            [
            ]
        );
    }

    private function GenerateSub(int $userID, $tokenCount = 5, $logs = 25): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $sub = Subscription::factory()
            ->has(PersonalToken::factory()->count($tokenCount))
            ->create(['user_id' => $userID, 'plan_id' => Plan::query()->first()->id]);

        UserLog::factory()->count($logs)->create([
            'subscription_id' => $sub->id,
        ]);

        return $sub;
    }

    /**
     * @test
     */
    public function UpdateSub(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $request = $this->json('PUT', "/sys-bin/admin/subscriptions/{$sub->id}", [
            'plan_id' => Plan::query()->skip(1)->first()->id,
        ], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertSame('0', json_decode($request->response->getContent())->user_id);
        self::assertSame(Plan::query()->skip(1)->first()->id, json_decode($request->response->getContent())->plan->id);
    }

    /**
     * @test
     */
    public function AuthorizeDeleteSubPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);

        $sub = $this->GenerateSub(0);
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/subscriptions/{$sub->id}", [
            'subscriptions:*' => 204,
        ]);

        $sub = $this->GenerateSub(0);
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/subscriptions/{$sub->id}", [
            'subscriptions:delete' => 204,
        ]);

        $sub = $this->GenerateSub(0);
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/subscriptions/{$sub->id}", [
            '' => 401,
        ]);
    }

    /**
     * @test
     */
    public function DeleteSub(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $request = $this->json('DELETE', "/sys-bin/admin/subscriptions/{$sub->id}", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(204);
    }

    /**
     * @test
     */
    public function AuthorizeGetSubPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $this->TestPermissions($token, $key, 'GET', "/sys-bin/admin/subscriptions/{$sub->id}", [
            'subscriptions:*'    => 200,
            ''                   => 401,
            'subscriptions:view' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSub(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertSame('0', json_decode($request->response->getContent())->user_id);
    }

    /**
     * @test
     */
    public function AuthorizeGetSubsPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $this->TestPermissions($token, $key, 'GET', '/sys-bin/admin/subscriptions/', [
            'subscriptions:*'        => 200,
            ''                       => 401,
            'subscriptions:view-all' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSubs(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);
        $sub = $this->GenerateSub(0);

        $request = $this->json('GET', '/sys-bin/admin/subscriptions/', [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(2, json_decode($request->response->getContent())->items);

        $request = $this->json('GET', '/sys-bin/admin/subscriptions/?limit=1', [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(1, json_decode($request->response->getContent())->items);
    }

    /**
     * @test
     */
    public function AuthorizeGetSubLogs(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);

        $this->TestPermissions($token, $key, 'GET', "/sys-bin/admin/subscriptions/{$sub->id}/logs", [
            'subscriptions:*'    => 200,
            ''                   => 401,
            'subscriptions:logs' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetSubLogs(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0);
        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}/logs", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(25, json_decode($request->response->getContent())->items);

        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}/logs/?limit=10", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(10, json_decode($request->response->getContent())->items);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Plan::factory()->count(3)->create();
    }
}
