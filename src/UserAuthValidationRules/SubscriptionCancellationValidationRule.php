<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Carbon\Carbon;
use function config;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCancellationValidationRule extends ValidationRuleBase
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->subscriptionRepository = Container::getInstance()->make(SubscriptionRepository::class);
    }

    public function Validate(): bool|array
    {
        $subscription = Subscriptions::getSubscription();

        if (!empty($subscription->cancels_at) && Carbon::now()->gte($subscription->cancels_at)) {
            $this->subscriptionRepository->Update($subscription->id, [
                'status'       => SubscriptionStatus::CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);

            if (!config('volistx.fallback_plan.id')) {
                return [
                    'message' => Messages::E403('Your plan has been cancelled. Please subscribe to a new plan if you want to continue using this service.'),
                    'code'    => 403,
                ];
            }

            $updatedSub = $this->subscriptionRepository->Clone($subscription->id, [
                'plan_id'      => config('volistx.fallback_plan.id'),
                'status'       => SubscriptionStatus::ACTIVE,
                'expires_at'   => null,
                'cancels_at'   => null,
                'cancelled_at' => null,
            ]);

            //set subscription for proceeding
            Subscriptions::setSubscription($updatedSub);
        }

        return true;
    }
}
