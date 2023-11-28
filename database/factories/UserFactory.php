<?php

namespace Volistx\FrameworkKernel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Volistx\FrameworkKernel\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid(),
            'is_active' => true,
        ];
    }
}
