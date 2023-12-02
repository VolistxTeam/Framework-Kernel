<?php

namespace Volistx\FrameworkKernel\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Volistx\FrameworkKernel\Helpers\KeysCenter;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\AccessToken;

class AccessTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccessToken::class;

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
            'key'                  => substr($key, 0, 32),
            'secret'               => SHA256Hasher::make(substr($key, 32), ['salt' => $salt]),
            'secret_salt'          => $salt,
            'permissions'          => [],
            'ip_rule'              => 0,
            'ip_range'             => [],
            'country_rule'         => 0,
            'country_range'        => [],
            'created_at'           => Carbon::now(),
        ];
    }
}
