<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Events\SubscriptionExpired;
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
