<?php
namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Plans service.
 *
 * @method static setPlan(object $plan) Set the current plan.
 * @method static getPlan() Get the current plan.
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