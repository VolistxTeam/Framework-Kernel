<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\SubscriptionExpired;
use Volistx\FrameworkKernel\Facades\Requests;

class SubscriptionExpiredListener
{
    public function handle(SubscriptionExpired $event)
    {
        $url = config('webhooks.subscription.expired.url');
        $token = config('webhooks.subscription.expired.token');

        if ($event->attempt_number > 3 || !$url || !$token) {
            return;
        }

        $response = Requests::Post($url, $token, [
            'subscription_id' => $event->subscription_id,
            'user_id' => $event->user_id
        ]);

        if ($response->isError) {
            event(new SubscriptionExpired($event->subscription_id, $event->user_id, $event->attempt_number + 1));
        }
    }
}
