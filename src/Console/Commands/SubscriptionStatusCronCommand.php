<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionStatusCronCommand extends Command
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
    }

    protected $signature = 'volistx-subscription:cron';

    protected $description = 'Check subscriptions and update them';

    //this function should be written directly in SQL to optimize and reduce server headache.
    public function handle(): void
    {
        $subscriptions = Subscription::query()
            ->where('status', '=', SubscriptionStatus::ACTIVE->value);

        foreach ($subscriptions as $subscription) {
            if (Carbon::now()->greaterThan(Carbon::createFromTimeString($subscription->expires_at))) {
                $this->subscriptionRepository->Update($subscription->id, [
                    'status'     => SubscriptionStatus::EXPIRED,
                    'expired_at' => Carbon::now(),
                ]);
            }

            if (Carbon::now()->greaterThan(Carbon::createFromTimeString($subscription->cancels_at))) {
                $this->subscriptionRepository->Update($subscription->id, [
                    'status'       => SubscriptionStatus::CANCELLED,
                    'cancelled_at' => Carbon::now(),
                ]);
            }

            if (config('volistx.fallback_plan.id') !== null) {
                $this->subscriptionRepository->Create([
                    'user_id'      => $subscription->user_id,
                    'plan_id'      => config('volistx.fallback_plan.id'),
                    'status'       => SubscriptionStatus::ACTIVE,
                    'activated_at' => Carbon::now(),
                    'expires_at'   => null,
                    'cancels_at'   => null,
                    'cancelled_at' => null,
                ]);
            }
        }

        $this->components->info('Subscription cron job has been completed.');
    }
}
