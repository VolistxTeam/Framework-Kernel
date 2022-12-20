<?php

namespace Volistx\FrameworkKernel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpired
{
    use InteractsWithSockets;
    use SerializesModels;

    public int $subscription_id;
    public int $attempt_number;
    public string $user_id;

    public function __construct(int $subscription_id, string $user_id, int $attempt_number = 0)
    {
        $this->subscription_id = $subscription_id;
        $this->user_id = $user_id;
        $this->attempt_number = $attempt_number;
    }
}
