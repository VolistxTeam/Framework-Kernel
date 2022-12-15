<?php

namespace Volistx\FrameworkKernel\Console\Commands\Volistx;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Events\SubscriptionExpired;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class CheckSubscriptionExpired extends Command
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
    }

    protected $signature = 'volistx:check-subscription-expired';

    protected $description = 'Check subscriptions that expired and update in the database.';

    /**
     * @return void
     */
    public function handle()
    {
        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('cancels_at', '>', Carbon::now());

        foreach ($subscriptions as $subscription) {
            Event::dispatch(SubscriptionExpired::class, [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
        }
    }
}
