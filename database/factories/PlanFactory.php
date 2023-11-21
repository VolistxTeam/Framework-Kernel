<?php

namespace Volistx\FrameworkKernel\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Volistx\FrameworkKernel\Models\Plan;

class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $n = $this->faker->numberBetween(1, 50000000);

        return [
            'name'  => "plan$n",
            'tag' => "$n",
            'description' => "random desc $n",
            'is_active' => true,
            'data' =>['requests' => $this->faker->numberBetween(100, 5000)] ,
            'price' => 10,
            'custom' => false,
            'tier' => $n,
            'created_at'  => Carbon::now()
        ];
    }
}
