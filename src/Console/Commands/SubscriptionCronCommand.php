<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCronCommand extends Command
{
    protected $signature = 'volistx-subscription:cron';

    protected $description = 'Check subscriptions and update them';

    public function handle(): void
    {
        $subscriptions = Subscription::query()->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->plan_cancels_at && Carbon::now()->gte($subscription->plan_cancels_at)) {
                if (config('volistx.fallback_plan.id') !== null) {
                    $repo = new SubscriptionRepository();
                    $repo->SwitchToFreePlan($subscription->id);
                }
            }
        }
    }
}
