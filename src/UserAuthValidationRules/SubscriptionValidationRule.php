<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Carbon\Carbon;
use function config;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionValidationRule extends ValidationRuleBase
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->subscriptionRepository = Container::getInstance()->make(SubscriptionRepository::class);
    }

    public function Validate(): bool|array
    {
        $user_id = PersonalTokens::getToken()->user_id;

        $activeSubscription = $this->subscriptionRepository->FindUserActiveSubscription($user_id);

        if ($activeSubscription) {
            $subStatusModified = $this->UpdateSubscriptionExpiryOrCancelStatus($activeSubscription);

            //Current active sub is totally valid, set facades and proceed with next validation rules
            if ($subStatusModified === false) {
                Subscriptions::setSubscription($activeSubscription);
                Plans::setPlan($activeSubscription->plan);

                return true;
            }
        }

        $inactiveSubscription = $this->subscriptionRepository->FindUserInactiveSubscription($user_id);

        if ($inactiveSubscription && Carbon::now()->gte($inactiveSubscription->activated_at)) {
            //update the sub to active if activation date is in the past
            $this->subscriptionRepository->Update($inactiveSubscription->id, [
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $subStatusModified = $this->UpdateSubscriptionExpiryOrCancelStatus($inactiveSubscription);

            if ($subStatusModified === false) {
                Subscriptions::setSubscription($inactiveSubscription);
                Plans::setPlan($inactiveSubscription->plan);

                return true;
            }
        }

        //user dont have any valid active or inactive subscriptions so we resort to fall-back plan
        if (!config('volistx.fallback_plan.id')) {
            return [
                'message' => Messages::E403('Your subscription has been expired. Please subscribe to a new plan if you want to continue using this service.'),
                'code' => 403,
            ];
        }


        $fall_back_subscription = $this->subscriptionRepository->Create([
            'user_id' => $user_id,
            'plan_id' => config('volistx.fallback_plan.id'),
            'status' => SubscriptionStatus::ACTIVE,
            'activated_at' => Carbon::now(),
            'expires_at' => null,
            'cancels_at' => null,
            'cancelled_at' => null,
        ]);

        Subscriptions::setSubscription($fall_back_subscription);
        Plans::setPlan($fall_back_subscription->plan);

        return true;
    }

    private function UpdateSubscriptionExpiryOrCancelStatus($subscription): bool
    {
        if (!empty($subscription->expires_at) && Carbon::now()->gte($subscription->expires_at)) {
            $this->subscriptionRepository->Update($subscription->id, [
                'status' => SubscriptionStatus::EXPIRED,
                'expired_at' => Carbon::now(),
            ]);

            if($this->IsInGracePeriod($subscription)){
                return false;
            }

            return true;
        }

        if (!empty($subscription->cancels_at) && Carbon::now()->gte($subscription->cancels_at)) {
            $this->subscriptionRepository->Update($subscription->id, [
                'status' => SubscriptionStatus::CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);

            return true;
        }

        return false;
    }

    private function IsInGracePeriod($subscription): bool
    {
        return Carbon::now()->lte(Carbon::createFromFormat('Y-m-d H:i:s', $subscription->expires_at)->addDays($subscription->plan->data['grace_period']));
    }
}
