<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
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
            $this->subscriptionRepository->Update($subscription->id, [
                'status'            => SubscriptionStatus::CANCELLED,
                'plan_cancelled_at' => Carbon::now(),
            ]);

            $this->subscriptionRepository->Clone($subscription->id, [
                'plan_id'         => config('volistx.fallback_plan.id'),
                'plan_expires_at' => null,
            ]);

            //Need to associate all of the old subscription personal tokens with new subscription or the code wont work.
        }

        $this->info('Subscription cron job has been completed.');
    }
}
