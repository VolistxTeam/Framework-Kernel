<?php

namespace Volistx\FrameworkKernel\Helpers;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Jobs\SubscriptionCancelled;
use Volistx\FrameworkKernel\Jobs\SubscriptionExpired;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionCenter
{
    private mixed $subscription = null;
    private SubscriptionRepository $subscriptionRepository;

    /**
     * SubscriptionCenter constructor.
     */
    public function __construct()
    {
        $this->subscriptionRepository = Container::getInstance()->make(SubscriptionRepository::class);
    }

    /**
     * Set the subscription.
     *
     * @param mixed $subscription The subscription
     *
     * @return void
     */
    public function setSubscription(mixed $subscription): void
    {
        $this->subscription = $subscription;
    }

    /**
     * Get the subscription.
     *
     * @return mixed The subscription
     */
    public function getSubscription(): mixed
    {
        return $this->subscription;
    }

    /**
     * Check if the subscription should be expired.
     *
     * @param mixed $subscription The subscription
     *
     * @return bool True if the subscription should be expired, false otherwise
     */
    public function shouldSubscriptionBeExpired(mixed $subscription): bool
    {
        return !empty($subscription->expires_at) && Carbon::now()->gte($subscription->expires_at);
    }

    /**
     * Check if the subscription should be cancelled.
     *
     * @param mixed $subscription The subscription
     *
     * @return bool True if the subscription should be cancelled, false otherwise
     */
    public function shouldSubscriptionBeCancelled(mixed $subscription): bool
    {
        return !empty($subscription->cancels_at) && Carbon::now()->gte($subscription->cancels_at);
    }

    /**
     * Update the expiry status of the subscription.
     *
     * @param string $userId       The user ID
     * @param mixed  $subscription The subscription
     *
     * @return bool True if the subscription expiry status was updated, false otherwise
     */
    public function updateSubscriptionExpiryStatus(string $userId, mixed $subscription): bool
    {
        if ($this->shouldSubscriptionBeExpired($subscription)) {
            $this->subscriptionRepository->update($userId, $subscription->id, [
                'status'     => SubscriptionStatus::EXPIRED,
                'expired_at' => Carbon::now(),
            ]);
            dispatch(new SubscriptionExpired($subscription->id, $subscription->user_id));

            return true;
        }

        return false;
    }

    /**
     * Update the cancellation status of the subscription.
     *
     * @param string $userId       The user ID
     * @param mixed  $subscription The subscription
     *
     * @return bool True if the subscription cancellation status was updated, false otherwise
     */
    public function updateSubscriptionCancellationStatus(string $userId, mixed $subscription): bool
    {
        if ($this->shouldSubscriptionBeCancelled($subscription)) {
            $this->subscriptionRepository->update($userId, $subscription->id, [
                'status'       => SubscriptionStatus::CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);
            dispatch(new SubscriptionCancelled($subscription->id, $subscription->user_id));

            return true;
        }

        return false;
    }

    /**
     * Process the status of the user's active subscriptions.
     *
     * @param string $userId The user ID
     *
     * @return mixed The active subscription if valid, false otherwise
     */
    public function processUserActiveSubscriptionsStatus(string $userId): mixed
    {
        $activeSubscription = $this->subscriptionRepository->findUserActiveSubscription($userId);
        if ($activeSubscription) {
            $subStatusModified = $this->updateSubscriptionExpiryStatus($userId, $activeSubscription)
                || $this->updateSubscriptionCancellationStatus($userId, $activeSubscription);
            // Current active sub is totally valid, set facades and proceed with next validation rules
            if ($subStatusModified === false) {
                return $activeSubscription;
            }

            return false;
        }

        return $activeSubscription;
    }

    /**
     * Process the status of the user's inactive subscriptions.
     *
     * @param string $userId The user ID
     *
     * @return mixed The activated subscription if successful, false otherwise
     */
    public function processUserInactiveSubscriptionsStatus(string $userId): mixed
    {
        $inactiveSubscription = $this->subscriptionRepository->findUserInactiveSubscription($userId);
        if ($inactiveSubscription && Carbon::now()->gte($inactiveSubscription->activated_at)) {
            $this->subscriptionRepository->update($userId, $inactiveSubscription->id, [
                'status' => SubscriptionStatus::ACTIVE,
            ]);
            $subStatusModified = Subscriptions::updateSubscriptionExpiryStatus($userId, $inactiveSubscription)
                || Subscriptions::updateSubscriptionCancellationStatus($userId, $inactiveSubscription);
            if ($subStatusModified === false) {
                return $inactiveSubscription;
            }

            return false;
        }

        return $inactiveSubscription;
    }
}
