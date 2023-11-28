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
use Volistx\FrameworkKernel\DataTransferObjects\UserDTO;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\User;
use Volistx\FrameworkKernel\Models\UserLog;
use Volistx\FrameworkKernel\Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function AuthorizeCreateUserPermissions()
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);

        $this->TestPermissions($token, $key, 'post', "/sys-bin/admin/users", [
            'user:*' => 201,
            '' => 401,
            'user:create' => 201,
        ]);
    }

    /**
     * @test
     */
    public function CreateUser(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
        ])->post("/sys-bin/admin/users");

        $response->assertStatus(201);
    }


    /**
     * @test
     */
    public function AuthorizeUpdateUserPermissions()
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $user = User::query()->first();

        $this->TestPermissions($token, $key, 'patchJson', "/sys-bin/admin/users/$user->id", [
            'user:*' => 200,
            '' => 401,
            'user:update' => 200,
        ]);
    }

    /**
     * @test
     */
    public function UpdateUser(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);
        $user = User::query()->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->patchJson("/sys-bin/admin/users/$user->id", [
            'is_active' => false
        ]);

        $user = User::query()->first();
        $response->assertStatus(200);
        $response->assertJson(UserDTO::fromModel($user)->GetDTO());
    }

    /**
     * @test
     */
    public function AuthorizeDeleteUserPermissions()
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $user = User::query()->first();

        $this->TestPermissions($token, $key, 'delete', "/sys-bin/admin/users/$user->id", [
            '' => 401,
            'user:delete' => 204,
        ]);
    }

    /**
     * @test
     */
    public function DeleteUser(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);
        $user = User::query()->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->delete("/sys-bin/admin/users/$user->id");

        $response->assertStatus(204);
    }

    /**
     * @test
     */
    public function AuthorizeGetUserPermissions()
    {
        $key = Str::random(64);
        $token = $this->GenerateAccessToken($key);
        $user = User::query()->first();

        $this->TestPermissions($token, $key, 'get', "/sys-bin/admin/users/$user->id", [
            'user:*' => 200,
            '' => 401,
            'user:view' => 200,
        ]);
    }

    /**
     * @test
     */
    public function GetUser(): void
    {
        $key = Str::random(64);
        $this->GenerateAccessToken($key);
        $user = User::query()->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->get("/sys-bin/admin/users/$user->id");

        $response->assertStatus(200);
        $response->assertJson(UserDTO::fromModel($user)->GetDTO());
    }

    private function GenerateAccessToken(string $key): Collection|Model
    {
        $salt = Str::random(16);

        $token = AccessTokenFactory::new()
            ->create(['key' => substr($key, 0, 32),
                'secret' => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
                'secret_salt' => $salt,
                'permissions' => ['user:*'],]);

        UserFactory::new()->create();

        return $token;
    }
}
