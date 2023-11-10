<?php
namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the PersonalTokens service.
 *
 * @method static setToken(object $token) Set the personal token.
 * @method static getToken() Get the personal token.
 */
class PersonalTokens extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'PersonalTokens';
    }
}