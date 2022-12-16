<?php

namespace Volistx\FrameworkKernel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated
{
    use InteractsWithSockets;
    use SerializesModels;

    public int $subscription_id;

    public function __construct(int $subscription_id)
    {
        $this->subscription_id = $subscription_id;
    }
}
