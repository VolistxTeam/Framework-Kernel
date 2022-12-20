<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Events\SubscriptionExpired;
use Volistx\FrameworkKernel\Facades\Requests;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCancelledListener
{
    public function handle(SubscriptionCancelled $event)
    {
        $url = config('webhooks.subscription.cancelled.url');
        $token = config('webhooks.subscription.cancelled.token');

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
