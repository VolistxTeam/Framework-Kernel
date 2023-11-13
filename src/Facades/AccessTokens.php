<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the AccessTokens service.
 *
 * @method static mixed getToken() Get the access token.
 * @method static void setToken(mixed $token) Set the access token.
 */
class AccessTokens extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'AccessTokens';
    }
}