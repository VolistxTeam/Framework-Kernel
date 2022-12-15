<?php

namespace Volistx\FrameworkKernel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpired
{
    use InteractsWithSockets;
    use SerializesModels;

    public int $subscription_id;
    public int $user_id;

    public function __construct($array)
    {
        $this->subscription_id = $array['subscription_id'];
        $this->user_id = $array['user_id'];
    }
}
