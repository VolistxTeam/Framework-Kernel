<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCancelledListener
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function handle(SubscriptionCancelled $event)
    {
        $this->subscriptionRepository->Update($event->user_id, $event->subscription_id, [
            'status'     => SubscriptionStatus::CANCELLED,
            'expired_at' => Carbon::now(),
        ]);
    }
}
