<?php

namespace Volistx\FrameworkKernel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Volistx\FrameworkKernel\Facades\Requests;

class SubscriptionCancelled implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $subscription_id;
    public int $attempt_number;
    public int $user_id;

    public function __construct(string $subscription_id, int $user_id, int $attempt_number = 1)
    {
        $this->subscription_id = $subscription_id;
        $this->user_id = $user_id;
        $this->attempt_number = $attempt_number;
    }

    public function handle()
    {
        $url = config('volistx.webhooks.subscription.cancelled.url');
        $token = config('volistx.webhooks.subscription.cancelled.token');

        if ($this->attempt_number > 3 || !$url || !$token) {
            return;
        }

        $response = Requests::Post($url, $token, [
            'type'    => 'subscription_cancelled',
            'payload' => [
                'subscription_id' => $this->subscription_id,
                'user_id'         => $this->user_id,
            ],
        ]);

        if ($response->isError) {
            event(new SubscriptionCancelled($this->subscription_id, $this->user_id, $this->attempt_number + 1));
        }
    }
}
