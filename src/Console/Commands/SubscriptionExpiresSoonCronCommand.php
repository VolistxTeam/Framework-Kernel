<?php

namespace Volistx\FrameworkKernel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Jobs\SubscriptionExpiresSoon;
use Volistx\FrameworkKernel\Models\Subscription;

//should run once daily and will notify user than his sub expiring in one day
class SubscriptionExpiresSoonCronCommand extends Command
{
    protected $signature = 'volistx-subscription:cron';

    protected $description = 'Check all subscriptions and update them if needed';

    public function handle(): void
    {
        $subscriptions = Subscription::query()
            ->where([
                ['status', '=', SubscriptionStatus::ACTIVE->value],
                ['expires_at', '<', Carbon::now()->subDay()->format('Y-m-d H:i:s')],
            ]);

        foreach ($subscriptions as $subscription) {
            dispatch(new SubscriptionExpiresSoon($subscription->id, $subscription->user_id));
        }

        $this->components->info('Subscription cron job has been completed.');
    }
}
