<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Carbon\Carbon;
use function config;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Events\SubscriptionCreated;
use Volistx\FrameworkKernel\Events\SubscriptionExpired;
use Volistx\FrameworkKernel\Events\SubscriptionUpdated;
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
            $subStatusModified = $this->UpdateSubscriptionExpiryOrCancelStatus($user_id, $activeSubscription);

            // Current active sub is totally valid, set facades and proceed with next validation rules
            if ($subStatusModified === false) {
                Subscriptions::setSubscription($activeSubscription);
                Plans::setPlan($activeSubscription->plan);

                return true;
            }
        }

        $inactiveSubscription = $this->subscriptionRepository->FindUserInactiveSubscription($user_id);

        if ($inactiveSubscription && Carbon::now()->gte($inactiveSubscription->activated_at)) {
            $this->subscriptionRepository->Update($user_id, $inactiveSubscription->id, [
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            Event::dispatch(new SubscriptionUpdated($inactiveSubscription->id));

            $subStatusModified = $this->UpdateSubscriptionExpiryOrCancelStatus($user_id, $inactiveSubscription);

            if ($subStatusModified === false) {
                Subscriptions::setSubscription($inactiveSubscription);
                Plans::setPlan($inactiveSubscription->plan);

                return true;
            }
        }

        // The user don't have any valid active or inactive subscriptions, so we resort to fall-back plan
        if (!config('volistx.fallback_plan.id')) {
            return [
                'message' => Messages::E403(trans('volistx::subscription.expired')),
                'code'    => 403,
            ];
        }

        $freeSubscription = $this->subscriptionRepository->Create([
            'user_id'      => $user_id,
            'plan_id'      => config('volistx.fallback_plan.id'),
            'status'       => SubscriptionStatus::ACTIVE,
            'activated_at' => Carbon::now(),
            'expires_at'   => null,
            'cancels_at'   => null,
            'cancelled_at' => null,
        ]);

        Event::dispatch(new SubscriptionCreated($freeSubscription->id));

        Subscriptions::setSubscription($freeSubscription);
        Plans::setPlan($freeSubscription->plan);

        return true;
    }

    private function UpdateSubscriptionExpiryOrCancelStatus($user_id, $subscription): bool
    {
        if (!empty($subscription->expires_at) && Carbon::now()->gte($subscription->expires_at)) {
            $this->subscriptionRepository->Update($user_id, $subscription->id, [
                'status'     => SubscriptionStatus::EXPIRED,
                'expires_at' => Carbon::now(),
            ]);

            Event::dispatch(new SubscriptionExpired($subscription->id));

            return true;
        }

        if (!empty($subscription->cancels_at) && Carbon::now()->gte($subscription->cancels_at)) {
            $this->subscriptionRepository->Update($user_id, $subscription->id, [
                'status'       => SubscriptionStatus::CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);

            Event::dispatch(new SubscriptionCancelled($subscription->id));

            return true;
        }

        return false;
    }
}
