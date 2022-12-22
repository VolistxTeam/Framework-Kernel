<?php

namespace Volistx\FrameworkKernel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Volistx\FrameworkKernel\Facades\Requests;

class SubscriptionExpired implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $subscription_id;
    public int $attempt_number;
    public string $user_id;

    public function __construct(int $subscription_id, string $user_id, int $attempt_number = 1)
    {
        $this->subscription_id = $subscription_id;
        $this->user_id = $user_id;
        $this->attempt_number = $attempt_number;
    }

    public function handle()
    {
        $url = config('webhooks.subscription.expired.url');
        $token = config('webhooks.subscription.expired.token');

        if ($this->attempt_number > 3 || !$url || !$token) {
            return;
        }

        $response = Requests::Post($url, $token, [
            'subscription_id' => $this->subscription_id,
            'user_id'         => $this->user_id,
        ]);

        if ($response->isError) {
            dispatch(new SubscriptionExpired($this->subscription_id, $this->user_id, $this->attempt_number + 1));
        }
    }
}
