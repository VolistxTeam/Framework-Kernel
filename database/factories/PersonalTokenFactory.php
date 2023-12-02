<?php

namespace Volistx\FrameworkKernel\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Helpers\KeysCenter;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\PersonalToken;

class PersonalTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PersonalToken::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $key = KeysCenter::randomKey(64);
        $salt = KeysCenter::randomKey(16);

        return [
            'user_id'         => Str::ulid()->toRfc4122(),
            'name'            => $this->faker->name(),
            'key'             => substr($key, 0, 32),
            'secret'          => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
            'secret_salt'     => $salt,
            'rate_limit_mode' => RateLimitMode::SUBSCRIPTION,
            'permissions'     => ['*'],
            'ip_rule'         => AccessRule::NONE,
            'ip_range'        => [],
            'country_rule'    => AccessRule::NONE,
            'country_range'   => [],
            'hmac_token'      => 'whatever',
            'created_at'      => Carbon::now(),
            'activated_at'    => Carbon::now(),
            'hidden'          => false,
            'disable_logging' => false,
        ];
    }
}
