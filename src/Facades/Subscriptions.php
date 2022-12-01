<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getSubscription()
 * @method static setSubscription(Builder|Model $activeSubscription)
 */
class Subscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Subscriptions';
    }
}
