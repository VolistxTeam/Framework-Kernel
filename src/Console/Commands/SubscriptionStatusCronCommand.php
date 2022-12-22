<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
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
            ->where([
                ['status', '=', SubscriptionStatus::ACTIVE->value],
                ['expires_at', '<', Carbon::now()],
            ])
            ->orWhere([
                ['status', '=', SubscriptionStatus::INACTIVE->value],
                ['expires_at', '<', Carbon::now()],
            ])
            ->orWhere([
                ['status', '=', SubscriptionStatus::ACTIVE->value],
                ['cancels_at', '<', Carbon::now()],
            ])
            ->orWhere([
                ['status', '=', SubscriptionStatus::INACTIVE->value],
                ['cancels_at', '<', Carbon::now()],
            ])
            ->get();

        foreach ($subscriptions as $subscription) {
            Subscriptions::UpdateSubscriptionExpiryStatus($subscription->user_id, $subscription);
            Subscriptions::UpdateSubscriptionCancellationStatus($subscription->user_id, $subscription);
        }

        $this->components->info('Subscription cron job has been completed.');
    }
}
