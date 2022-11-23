<?php

namespace Volistx\FrameworkKernel\RequestPreProcessors;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionExpiryPreProcessor extends RequestPreProcessorBase
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(array $inputs)
    {
        parent::__construct($inputs);
        $this->subscriptionRepository = Container::getInstance()->make(SubscriptionRepository::class);
    }

    public function Process(): bool|array
    {
        $subscription = $this->inputs['subscription'];

        if ($subscription->status === SubscriptionStatus::ACTIVE && !empty($subscription->expires_at) && Carbon::now()->gte($subscription->expires_at)) {
            $this->subscriptionRepository->Update($subscription->id, [
                'status'            => SubscriptionStatus::EXPIRED,
                'expired_at'        => Carbon::now(),
            ]);

            if (!config('volistx.fallback_plan.id')) {
                return [
                    'message' => Messages::E403('Your plan has been cancelled. Please subscribe to a new plan if you want to continue using this service.'),
                    'code'    => 403,
                ];
            }

            $this->subscriptionRepository->Clone($subscription->id, [
                'plan_id'         => config('volistx.fallback_plan.id'),
                'expires_at'      => null,
            ]);
        }

        return true;
    }
}
