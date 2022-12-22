<?php

namespace Volistx\FrameworkKernel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Volistx\FrameworkKernel\Facades\Requests;

class SubscriptionCancelled implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
        $url = config('webhooks.subscription.cancelled.url');
        $token = config('webhooks.subscription.cancelled.token');

        if ($this->attempt_number > 3 || !$url || !$token) {
            return;
        }

        $response = Requests::Post($url, $token, [
            'subscription_id' => $this->subscription_id,
            'user_id' => $this->user_id
        ]);

        if ($response->isError) {
            event(new SubscriptionCancelled($this->subscription_id, $this->user_id, $this->attempt_number + 1));
        }
    }
}