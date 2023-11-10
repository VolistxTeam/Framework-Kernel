<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getSubscription()
 * @method static setSubscription(Builder|Model $activeSubscription)
 * @method static ShouldSubscriptionBeExpired(object $subscription)
 * @method static ShouldSubscriptionBeCancelled(object $subscription)
 * @method static UpdateSubscriptionExpiryStatus(string $user_id, Builder|Model $activeSubscription)
 * @method static UpdateSubscriptionCancellationStatus(string $user_id, Builder|Model $activeSubscription)
 * @method static ProcessUserActiveSubscriptionsStatus(string $user_id)
 * @method static ProcessUserInactiveSubscriptionsStatus(string $user_id)
 */
class Subscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Subscriptions';
    }
}
