<?php
namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Jobs\SubscriptionExpiresSoon;
use Volistx\FrameworkKernel\Models\Subscription;

/**
 * Cron command to check subscriptions that are expiring soon and send webhooks to them.
 */
class SubscriptionExpiresSoonCronCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'volistx-subscription:expire-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check subscriptions that expire soon and send webhooks to them';

    /**
     * Handle the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // Get subscriptions that are active and expiring within one day
        $subscriptions = Subscription::query()
            ->where([
                ['status', SubscriptionStatus::ACTIVE->value],
                ['expires_at', '<', Carbon::now()->addDay()],
                ['expires_at', '>', Carbon::now()],
            ])
            ->get();

        // Dispatch the SubscriptionExpiresSoon job for each subscription
        foreach ($subscriptions as $subscription) {
            dispatch(new SubscriptionExpiresSoon($subscription->id, $subscription->user_id));
        }

        $this->info('Subscription cron job has been completed.');
    }
}