<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static getToken()
 * @method static setToken(object $token)
 */
class AccessTokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'AccessTokens';
    }
}
