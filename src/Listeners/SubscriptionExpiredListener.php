<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionExpiredListener
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function handle(SubscriptionCancelled $event)
    {
    }
}
