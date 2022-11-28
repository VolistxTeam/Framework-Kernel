<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static setPlan($plan)
 * @method static getPlan()
 */
class Plans extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Plans';
    }
}
