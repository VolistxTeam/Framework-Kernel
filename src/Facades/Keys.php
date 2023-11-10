<?php
namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for generating random salted keys.
 *
 * @method static randomSaltedKey() Generate a random salted key.
 */
class Keys extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Keys';
    }
}