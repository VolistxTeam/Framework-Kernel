<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static setToken(object $token)
 */
class PersonalTokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'PersonalTokens';
    }
}
