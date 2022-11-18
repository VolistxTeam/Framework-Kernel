<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static getSubscription()
 * @method static setSubscription(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model $activeSubscription)
 */
class Subscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Subscriptions';
    }
}
