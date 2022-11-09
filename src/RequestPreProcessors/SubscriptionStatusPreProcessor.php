<?php

namespace Volistx\FrameworkKernel\RequestPreProcessors;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionStatusPreProcessor extends RequestPreProcessorBase
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(array $inputs, SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct($inputs);
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function Process(): bool|array
    {
        $subscription = $this->inputs['token']->subscription()->first();

        if ($subscription->plan_cancels_at && Carbon::now()->gte($subscription->plan_cancels_at)) {
            if (!config('volistx.fallback_plan.id')) {
                return [
                    'message' => Messages::E403('Your plan has been cancelled. Please subscribe to a new plan if you want to continue using this service.'),
                    'code'    => 403,
                ];
            }

            $this->subscriptionRepository->SwitchToFreePlan($subscription->id);
        }

        return true;
    }
}
