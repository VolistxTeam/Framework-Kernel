<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;


/**
 * Facade for generating random salted keys.
 *
 * @method static array randomSaltedKey(int $keyLength = 64, int $saltLength = 16) Generate a random salted key.
 * @method static string randomKey(int $length = 64) Generate a random key.
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