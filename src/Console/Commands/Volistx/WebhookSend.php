<?php

namespace Volistx\FrameworkKernel\Console\Commands\Volistx;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Models\Subscription;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class WebhookSend extends Command
{
    // THIS IS THE DRAFT OF THE WEBHOOK IDEA. IT REQUIRED TO BE REFACTORED AND TESTED.

    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
    }

    protected $signature = 'volistx:send-webhook-subscription-expire-soon';

    protected $description = 'Send a webhook to all subscriptions that expire tomorrow.';

    /**
     * @return void
     */
    public function handle()
    {
        if (empty(config('volistx.webhooks.subscription.expires_soon.url'))) {
            return;
        }

        $subscriptions = Subscription::query()
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->where('expires_at', '<', Carbon::tomorrow())
            ->get();

        foreach ($subscriptions as $subscription) {
            // IT MUST BE QUEUED ASYNCHRONOUSLY TO AVOID DELAYS OR FAILURES.
            // IT MUST BE REFACTORED TO USE WEBHOOK HELPER.

            $data = [
                'eventType' => 'subscription.expire_soon',
                'payload'   => [
                    'subscription' => $subscription,
                ],
            ];

            $ch = curl_init(config('volistx.webhooks.subscription.expires_soon.url'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer '.config('volistx.webhooks.subscription.expires_soon.token'),
                'Content-Type: application/json',
            ]);

            curl_exec($ch);
        }
    }
}
