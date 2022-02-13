<?php

namespace VolistxTeam\VSkeletonKernel\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use VolistxTeam\VSkeletonKernel\Models\Plan;

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
            'name'        => "plan$n",
            'description' => $this->faker->text(),
            'data'        => ['requests' => $this->faker->numberBetween(100, 5000)],
            'created_at'  => Carbon::now(),
        ];
    }
}
