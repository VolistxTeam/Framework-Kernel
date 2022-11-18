<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static randomSaltedKey()
 */
class Keys extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Keys';
    }
}
