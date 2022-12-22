<?php

namespace Volistx\FrameworkKernel\Helpers;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Event;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Jobs\SubscriptionCancelled;
use Volistx\FrameworkKernel\Jobs\SubscriptionExpired;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCenter
{
    private $subscription;
    private SubscriptionRepository $subscriptionRepository;

    public function __construct()
    {
        $this->subscriptionRepository = Container::getInstance()->make(SubscriptionRepository::class);
    }

    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
    }

    public function getSubscription()
    {
        return $this->subscription;
    }

    public function ShouldSubscriptionBeExpired($subscription): bool
    {
        return !empty($subscription->expires_at) && Carbon::now()->gte($subscription->expires_at);
    }

    public function ShouldSubscriptionBeCancelled($subscription): bool
    {
        return !empty($subscription->cancels_at) && Carbon::now()->gte($subscription->cancels_at);
    }

    //returns true in case of update happened
    private function UpdateSubscriptionExpiryStatus($user_id, $subscription): bool
    {
        if ($this->ShouldSubscriptionBeExpired($subscription)) {
            $this->subscriptionRepository->Update($user_id, $subscription->id, [
                'status' => SubscriptionStatus::EXPIRED,
                'expires_at' => Carbon::now(),
            ]);

            dispatch(new SubscriptionExpired($subscription->id, $subscription->user_id));

            return true;
        }

        return false;
    }

    //returns true in case of update happened
    private function UpdateSubscriptionCancellationStatus($user_id, $subscription): bool
    {
        if ($this->ShouldSubscriptionBeCancelled($subscription)) {
            $this->subscriptionRepository->Update($user_id, $subscription->id, [
                'status' => SubscriptionStatus::CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);

            dispatch(new SubscriptionCancelled($subscription->id,$subscription->user_id));

            return true;
        }

        return false;
    }

    //return the active sun id if its valid, otherwise returns false
    private function ProcessUserActiveSubscriptionsStatus($user_id)
    {
        $activeSubscription = $this->subscriptionRepository->FindUserActiveSubscription($user_id);

        if ($activeSubscription) {
            $subStatusModified = $this->UpdateSubscriptionExpiryStatus($user_id, $activeSubscription)
                || $this->UpdateSubscriptionCancellationStatus($user_id, $activeSubscription);

            // Current active sub is totally valid, set facades and proceed with next validation rules
            if ($subStatusModified === false) {
                return $activeSubscription;
            }

            return false;
        }

        return $activeSubscription;
    }

    //Returns the sub id if it get activated, otherwise returns false
    private function ProcessUserInactiveSubscriptionsStatus($user_id)
    {
        $inactiveSubscription = $this->subscriptionRepository->FindUserInactiveSubscription($user_id);

        if ($inactiveSubscription && Carbon::now()->gte($inactiveSubscription->activated_at)) {
            $this->subscriptionRepository->Update($user_id, $inactiveSubscription->id, [
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $subStatusModified = Subscriptions::UpdateSubscriptionExpiryStatus($user_id, $inactiveSubscription)
                || Subscriptions::UpdateSubscriptionCancellationStatus($user_id, $inactiveSubscription);

            if ($subStatusModified === false) {
                return $inactiveSubscription;
            }

            return false;
        }

        return $inactiveSubscription;
    }

}
