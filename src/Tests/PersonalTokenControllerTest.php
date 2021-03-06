<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Support\Carbon;
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

class PersonalTokenControllerTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function createApplication(): Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * @test
     */
    public function AuthorizeCreateTokenPermissions(): void
    {
        $key = Str::random(64);
        $accessToken = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 0);

        $this->TestPermissions($accessToken, $key, 'POST', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/", [
            'personal-tokens:*'      => 201,
            ''                       => 401,
            'personal-tokens:create' => 201,
        ], [
            'permissions'     => ['*'],
            'whitelist_range' => ['127.0.0.0'],
            'duration'        => 500,
        ]);
    }

    private function GenerateAccessToken(string $key): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $salt = Str::random(16);

        return AccessToken::factory()
            ->create(['key'   => substr($key, 0, 32),
                'secret'      => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
                'secret_salt' => $salt,
                'permissions' => ['personal-tokens:*'], ]);
    }

    private function GenerateSub(int $userID, int $tokenCount, $logs = 50): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $sub = Subscription::factory()
            ->has(PersonalToken::factory()->count($tokenCount))
            ->create(['user_id' => $userID, 'plan_id' => Plan::query()->first()->id]);

        UserLog::factory()->count($logs)->create([
            'subscription_id' => $sub->id,
        ]);

        return $sub;
    }

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
    public function CreateToken(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 0);

        $request = $this->json('POST', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/", [
            'permissions'     => ['*'],
            'whitelist_range' => ['127.0.0.0'],
            'duration'        => 500,
        ], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(201);
        self::assertSame($sub->id, json_decode($request->response->getContent())->subscription->id);
        self::assertSame(['*'], json_decode($request->response->getContent())->permissions);
        self::assertSame(['127.0.0.0'], json_decode($request->response->getContent())->whitelist_range);
        self::assertSame(Carbon::createFromTimeString((json_decode($request->response->getContent())->token_status->activated_at))->addHours(500)->format('Y-m-d H:i:s'), json_decode($request->response->getContent())->token_status->expires_at);
    }

    /**
     * @test
     */
    public function AuthorizeUpdateTokenPermissions(): void
    {
        $key = Str::random(64);
        $accessToken = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 1);
        $personalToken = $sub->personalTokens()->first();

        $this->TestPermissions(
            $accessToken,
            $key,
            'PUT',
            "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}",
            [
                'personal-tokens:*'      => 200,
                'personal-tokens:update' => 200,
                ''                       => 401,
            ],
            [
            ]
        );
    }

    /**
     * @test
     */
    public function UpdateToken(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 1);
        $personalToken = $sub->personalTokens()->first();

        $request = $this->json('PUT', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [
            'permissions'     => ['1'],
            'whitelist_range' => ['128.0.0.0'],
            'duration'        => 1000,
        ], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertSame($sub->id, json_decode($request->response->getContent())->subscription->id);
        self::assertSame(['1'], json_decode($request->response->getContent())->permissions);
        self::assertSame(['128.0.0.0'], json_decode($request->response->getContent())->whitelist_range);
        $expires_at = json_decode($request->response->getContent())->token_status->expires_at;
        $activated_at = json_decode($request->response->getContent())->token_status->activated_at;
        self::assertSame(Carbon::createFromTimeString($activated_at)->addHours(1000)->timestamp, Carbon::createFromTimeString($expires_at)->timestamp);
    }

    /**
     * @test
     */
    public function AuthorizeResetTokenPermissions(): void
    {
        $key = Str::random(64);
        $accessToken = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 1);
        $personalToken = $sub->personalTokens()->first();

        $this->TestPermissions(
            $accessToken,
            $key,
            'PUT',
            "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}/reset",
            [
                'personal-tokens:*'     => 200,
                'personal-tokens:reset' => 200,
                ''                      => 401,
            ],
            [
            ]
        );
    }

    /**
     * @test
     */
    public function ResetToken(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 1);
        $personalToken = $sub->personalTokens()->first();
        $oldKey = $personalToken->key;

        $request = $this->json('PUT', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}/reset", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertNotSame($oldKey, json_decode($request->response->getContent())->key);
    }

    /**
     * @test
     */
    public function AuthorizeDeleteTokenPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 3);

        $personalToken = $sub->personalTokens()->first();
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [
            'personal-tokens:*' => 204,
        ]);

        $personalToken = $sub->personalTokens()->first();
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [
            'personal-tokens:delete' => 204,
        ]);

        $personalToken = $sub->personalTokens()->first();
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [
            '' => 401,
        ]);
    }

    /**
     * @test
     */
    public function DeleteToken(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 1);
        $personalToken = $sub->personalTokens()->first();

        $request = $this->json('DELETE', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(204);
    }

    /**
     * @test
     */
    public function AuthorizeGetTokenPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 1);
        $personalToken = $sub->personalTokens()->first();

        $this->TestPermissions($token, $key, 'GET', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [
            'personal-tokens:*'    => 200,
            ''                     => 401,
            'personal-tokens:view' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetToken(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 3);
        $personalToken = $sub->personalTokens()->first();

        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens/{$personalToken->id}", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertSame($personalToken->id, json_decode($request->response->getContent())->id);
    }

    /**
     * @test
     */
    public function AuthorizeGetTokensPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 3);

        $this->TestPermissions($token, $key, 'GET', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens", [
            'personal-tokens:*'        => 200,
            ''                         => 401,
            'personal-tokens:view-all' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetTokens(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GenerateSub(0, 3);

        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(3, json_decode($request->response->getContent())->items);

        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens?search=xxqsqeqeqw", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(0, json_decode($request->response->getContent())->items);

        $request = $this->json('GET', "/sys-bin/admin/subscriptions/{$sub->id}/personal-tokens?limit=2", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(2, json_decode($request->response->getContent())->items);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Plan::factory()->count(3)->create();
    }
}
