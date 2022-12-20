<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Models\Subscription;

class SubscriptionStatusCronCommand extends Command
{
    protected $signature = 'volistx-subscription:cron';

    protected $description = 'Check all subscriptions and update them if needed';

    public function handle(): void
    {
        $subscriptions = Subscription::query()
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->orWhere('status', '=', SubscriptionStatus::INACTIVE->value);

        foreach ($subscriptions as $subscription) {
            Subscriptions::UpdateSubscriptionExpiryStatus($subscription->user_id, $subscription);
            Subscriptions::UpdateSubscriptionCancellationStatus($subscription->user_id, $subscription);

            //currently, it doesnt create new free subscriptions for users, this part should be discussed
        }

        $this->components->info('Subscription cron job has been completed.');
    }

//    private function CreateFreeSubscriptionIfExist($subscription)
//    {
//        if (config('volistx.fallback_plan.id') !== null) {
//            $this->subscriptionRepository->Create([
//                'user_id' => $subscription->user_id,
//                'plan_id' => config('volistx.fallback_plan.id'),
//                'status' => SubscriptionStatus::ACTIVE,
//                'activated_at' => Carbon::now(),
//                'expires_at' => null,
//                'cancels_at' => null,
//                'cancelled_at' => null,
//            ]);
//        }
//    }
}
