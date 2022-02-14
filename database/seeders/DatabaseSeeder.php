<?php

namespace Volistx\FrameworkKernel\Database\Seeders;

use Illuminate\Database\Seeder;
use Volistx\FrameworkKernel\Models\PersonalToken;
use Volistx\FrameworkKernel\Models\Plan;
use Volistx\FrameworkKernel\Models\Subscription;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = Plan::factory()->count(3)->create();
        $subs = Subscription::factory()->for($plans[0])->count(50)->create();
        $tokens = PersonalToken::factory()->for($subs[0])->count(50)->create();
    }
}
