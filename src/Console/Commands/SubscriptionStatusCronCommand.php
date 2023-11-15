<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Models\Subscription;

/**
 * Cron command to check all subscriptions and update them if needed.
 */
class SubscriptionStatusCronCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'volistx-subscription:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all subscriptions and update them if needed';

    /**
     * Handle the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // Get subscriptions that need to be updated
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

        // Update subscription expiry and cancellation status
        foreach ($subscriptions as $subscription) {
            Subscriptions::UpdateSubscriptionExpiryStatus($subscription->user_id, $subscription);
            Subscriptions::UpdateSubscriptionCancellationStatus($subscription->user_id, $subscription);
        }

        $this->info('Subscription cron job has been completed.');
    }
}
