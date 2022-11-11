<?php

namespace Volistx\FrameworkKernel\RequestPreProcessors;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
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

        if ($subscription->status === SubscriptionStatus::SCHEDULED_TO_GET_CANCELLED && Carbon::now()->gte($subscription->plan_cancels_at)) {
            $this->subscriptionRepository->Update($subscription->id, [
                'status'            => SubscriptionStatus::CANCELLED,
                'plan_cancelled_at' => Carbon::now(),
            ]);

            if (!config('volistx.fallback_plan.id')) {
                return [
                    'message' => Messages::E403('Your plan has been cancelled. Please subscribe to a new plan if you want to continue using this service.'),
                    'code'    => 403,
                ];
            }

            $this->subscriptionRepository->Clone($subscription->id, [
                'plan_id'         => config('volistx.fallback_plan.id'),
                'plan_expires_at' => null,
            ]);

            //We need to associate the token with the new subscription or the code wont work as it will still use the old subscription..
            //TO DISCUSS.
        }

        return true;
    }
}
