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

    public string $subscriptionId;
    public int $attemptNumber;
    public string $userId;

    public function __construct(string $subscriptionId, string $userId, int $attemptNumber = 1)
    {
        $this->subscriptionId = $subscriptionId;
        $this->userId = $userId;
        $this->attemptNumber = $attemptNumber;
    }

    /**
     * Handle the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = config('volistx.webhooks.subscription.cancelled.url');
        $token = config('volistx.webhooks.subscription.cancelled.token');

        // Check if the attempt number exceeds the limit or if the URL or token is not provided
        if ($this->attemptNumber > 3 || !$url || !$token) {
            return;
        }

        $response = Requests::post($url, $token, [
            'type'    => 'subscription_cancelled',
            'payload' => [
                'subscription_id' => $this->subscriptionId,
                'user_id'         => $this->userId,
            ],
        ]);

        // Retry the job if the request fails
        if ($response->isError) {
            event(new SubscriptionCancelled($this->subscriptionId, $this->userId, $this->attemptNumber + 1));
        }
    }
}
