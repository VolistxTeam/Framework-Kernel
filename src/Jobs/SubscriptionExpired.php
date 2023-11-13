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

    public string $subscriptionId;
    public int $attemptNumber;
    public string $userId;

    public function __construct(string $subscriptionId, string $userId, int $attemptNumber = 1)
    {
        $this->subscriptionId = $subscriptionId;
        $this->userId = $userId;
        $this->attemptNumber = $attemptNumber;
    }

    public function handle()
    {
        $url = config('volistx.webhooks.subscription.expired.url');
        $token = config('volistx.webhooks.subscription.expired.token');

        if ($this->attemptNumber > 3 || !$url || !$token) {
            return;
        }

        $response = Requests::Post($url, $token, [
            'type' => 'subscription_expired',
            'payload' => [
                'subscription_id' => $this->subscriptionId,
                'user_id' => $this->userId,
            ],
        ]);

        if ($response->isError) {
            dispatch(new SubscriptionExpired($this->subscriptionId, $this->userId, $this->attemptNumber + 1));
        }
    }
}
