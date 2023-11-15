<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Plans service.
 *
 * @method static void  setPlan(mixed $plan) Set the current plan.
 * @method static mixed getPlan()            Get the current plan.
 */
class Plans extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Plans';
    }
}
