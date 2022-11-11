<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static setPlan($plan)
 */
class Plans extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Plans';
    }
}
