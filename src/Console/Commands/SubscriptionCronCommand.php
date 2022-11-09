<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCronCommand extends Command
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
    }

    protected $signature = 'volistx-subscription:cron';

    protected $description = 'Check subscriptions and update them';

    public function handle(): void
    {
        if (config('volistx.fallback_plan.id') === null) {
            $this->info('Subscription cron job has been completed. No Fall-back id detected and no changed were made');
            return;
        }

        $subscriptions = Subscription::query()->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->plan_cancels_at && Carbon::now()->gte($subscription->plan_cancels_at)) {
                $this->subscriptionRepository->SwitchToFreePlan($subscription->id);
            }
        }

        $this->info('Subscription cron job has been completed.');
    }
}
