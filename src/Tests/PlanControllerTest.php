<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Support\Str;
use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Volistx\FrameworkKernel\Classes\SHA256Hasher;
use Volistx\FrameworkKernel\Models\AccessToken;
use Volistx\FrameworkKernel\Models\Plan;
use Volistx\FrameworkKernel\Models\Subscription;

class PlanControllerTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function createApplication(): Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * @test
     */
    public function AuthorizeCreatePlanPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);

        $this->TestPermissions($token, $key, 'POST', '/sys-bin/admin/plans/', [
            'plans:*' => 201,
        ], [
            'name'        => 'name1',
            'description' => 'description',
            'data'        => ['requests' => 50],
        ]);

        $this->TestPermissions($token, $key, 'POST', '/sys-bin/admin/plans/', [
            '' => 401,
        ], [
            'name'        => 'name',
            'description' => 'description',
            'data'        => ['requests' => 50],
        ]);

        $this->TestPermissions($token, $key, 'POST', '/sys-bin/admin/plans/', [
            'plans:create' => 201,
        ], [
            'name'        => 'name2',
            'description' => 'description',
            'data'        => ['requests' => 50],
        ]);
    }

    private function GenerateAccessToken(string $key): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $salt = Str::random(16);

        return AccessToken::factory()
            ->create(['key'   => substr($key, 0, 32),
                'secret'      => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
                'secret_salt' => $salt,
                'permissions' => ['plans:*'], ]);
    }

    /**
     * @test
     *
     * @param int[] $permissions
     * @param (int[]|string)[] $input
     *
     * @psalm-param array{'plans:*'?: 200|201|204, ''?: 401, 'plans:create'?: 201, 'plans:update'?: 200, 'plans:delete'?: 204, 'plans:view'?: 200, 'plans:view-all'?: 200} $permissions
     * @psalm-param array{name?: 'name'|'name1'|'name2', description?: 'description', data?: array{requests: 50}} $input
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
    public function CreatePlan(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);

        $request = $this->json('POST', '/sys-bin/admin/plans/', [
            'name'        => 'name',
            'description' => 'description',
            'data'        => ['requests' => 50],
        ], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(201);
        self::assertSame('name', json_decode($request->response->getContent())->name);
        self::assertSame('description', json_decode($request->response->getContent())->description);
        self::assertSame(50, json_decode($request->response->getContent())->data->requests);
    }

    /**
     * @test
     */
    public function AuthorizeUpdatePlanPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $plan = $this->GeneratePlan();

        $this->TestPermissions(
            $token,
            $key,
            'PUT',
            "/sys-bin/admin/plans/{$plan->id}",
            [
                'plans:*'      => 200,
                'plans:update' => 200,
                ''             => 401,
            ],
            [
            ]
        );
    }

    private function GeneratePlan(int $subCount = 0): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        return Plan::factory()->has(Subscription::factory()->count($subCount))->create();
    }

    /**
     * @test
     */
    public function UpdatePlan(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $plan = $this->GeneratePlan();

        $request = $this->json('PUT', "/sys-bin/admin/plans/{$plan->id}", [
            'name' => 'UpdatedName',
        ], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertSame('UpdatedName', json_decode($request->response->getContent())->name);
    }

    /**
     * @test
     */
    public function AuthorizeDeletePlanPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);

        $plan = $this->GeneratePlan();
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/plans/{$plan->id}", [
            'plans:*' => 204,
        ]);

        $plan = $this->GeneratePlan();
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/plans/{$plan->id}", [
            'plans:delete' => 204,
        ]);

        $plan = $this->GeneratePlan();
        $this->TestPermissions($token, $key, 'DELETE', "/sys-bin/admin/plans/{$plan->id}", [
            '' => 401,
        ]);
    }

    /**
     * @test
     */
    public function DeleteNonDependantPlan(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $plan = $this->GeneratePlan();

        $request = $this->json('DELETE', "/sys-bin/admin/plans/{$plan->id}", [], [
            'Authorization' => "Bearer $key",
        ]);
        self::assertResponseStatus(204);
    }

    /**
     * @test
     */
    public function PreventDeleteDependantPlan(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);

        $plan = $this->GeneratePlan(5);
        $request = $this->json('DELETE', "/sys-bin/admin/plans/{$plan->id}", [], [
            'Authorization' => "Bearer $key",
        ]);
        self::assertResponseStatus(409);
    }

    /**
     * @test
     */
    public function AuthorizeGetPlanPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $plan = $this->GeneratePlan();

        $this->TestPermissions($token, $key, 'GET', "/sys-bin/admin/plans/{$plan->id}", [
            'plans:*'    => 200,
            ''           => 401,
            'plans:view' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetPlan(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $plan = $this->GeneratePlan();

        $request = $this->json('GET', "/sys-bin/admin/plans/{$plan->id}", [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertNotEmpty(json_decode($request->response->getContent())->name);
    }

    /**
     * @test
     */
    public function AuthorizeGetPlansPermissions(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $sub = $this->GeneratePlan();

        $this->TestPermissions($token, $key, 'GET', '/sys-bin/admin/plans/', [
            'plans:*'        => 200,
            ''               => 401,
            'plans:view-all' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetPlans(): void
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $this->GeneratePlan();
        $this->GeneratePlan();

        $request = $this->json('GET', '/sys-bin/admin/plans/', [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(2, json_decode($request->response->getContent())->items);

        $request = $this->json('GET', '/sys-bin/admin/plans/?limit=1', [], [
            'Authorization' => "Bearer $key",
        ]);

        self::assertResponseStatus(200);
        self::assertCount(1, json_decode($request->response->getContent())->items);
    }
}
