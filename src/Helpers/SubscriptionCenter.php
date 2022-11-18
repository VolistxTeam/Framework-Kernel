<?php

namespace Volistx\FrameworkKernel\Helpers;

class SubscriptionCenter
{
    private $subscription;

    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
    }

    public function getSubscription()
    {
        return $this->subscription;
    }
}
