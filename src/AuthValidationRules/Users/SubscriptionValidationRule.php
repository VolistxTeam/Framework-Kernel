<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

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

        $active_subscription = Subscriptions::ProcessUserActiveSubscriptionsStatus($user_id);
        if ($active_subscription) {
            Subscriptions::setSubscription($active_subscription);
            Plans::setPlan($active_subscription->plan);
            return true;
        }

        $inactive_subscription = Subscriptions::ProcessUserInactiveSubscriptionsStatus($user_id);
        if ($inactive_subscription) {
            Subscriptions::setSubscription($inactive_subscription);
            Plans::setPlan($inactive_subscription->plan);
            return true;
        }

        // The user don't have any valid active or inactive subscriptions, so we resort to fall-back plan
        if (!config('volistx.fallback_plan.id')) {
            return [
                'message' => Messages::E403(trans('volistx::subscription.expired')),
                'code' => 403,
            ];
        }

        $freeSubscription = $this->subscriptionRepository->Create([
            'user_id' => $user_id,
            'plan_id' => config('volistx.fallback_plan.id'),
            'status' => SubscriptionStatus::ACTIVE,
            'activated_at' => Carbon::now(),
            'expires_at' => null,
            'cancels_at' => null,
            'cancelled_at' => null,
        ]);

        Subscriptions::setSubscription($freeSubscription);
        Plans::setPlan($freeSubscription->plan);

        return true;
    }
}
