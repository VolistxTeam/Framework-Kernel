<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Subscriptions service.
 *
 * @method static void  setSubscription(mixed $subscription)                                      Set the subscription.
 * @method static mixed getSubscription()                                                         Get the subscription.
 * @method static bool  shouldSubscriptionBeExpired(mixed $subscription)                          Check if the subscription should be expired.
 * @method static bool  shouldSubscriptionBeCancelled(mixed $subscription)                        Check if the subscription should be cancelled.
 * @method static bool  updateSubscriptionExpiryStatus(string $userId, mixed $subscription)       Update the expiry status of the subscription.
 * @method static bool  updateSubscriptionCancellationStatus(string $userId, mixed $subscription) Update the cancellation status of the subscription.
 * @method static mixed processUserActiveSubscriptionsStatus(string $userId)                      Process the status of the user's active subscriptions.
 * @method static mixed processUserInactiveSubscriptionsStatus(string $userId)                    Process the status of the user's inactive subscriptions.
 */
class Subscriptions extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Subscriptions';
    }
}
