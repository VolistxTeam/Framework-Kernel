<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getSubscription()
 * @method static setSubscription(Builder|Model $activeSubscription)
 * @method static ShouldSubscriptionBeExpired($subscription)
 * @method static ShouldSubscriptionBeCancelled($subscription)
 * @method static UpdateSubscriptionExpiryStatus($user_id, Builder|Model $activeSubscription)
 * @method static UpdateSubscriptionCancellationStatus($user_id, Builder|Model $activeSubscription)
 * @method static ProcessUserActiveSubscriptionsStatus($user_id)
 * @method static ProcessUserInactiveSubscriptionsStatus($user_id)
 */

class Subscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Subscriptions';
    }
}
