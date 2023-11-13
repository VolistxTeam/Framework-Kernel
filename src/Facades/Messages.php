<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Messages service.
 *
 * @method static array Error(string $type, string $info) Create an error message.
 * @method static array E400(?string $error = null) Create a 400 Bad Request error message.
 * @method static array E401(?string $error = null) Create a 401 Unauthorized error message.
 * @method static array E403(?string $error = null) Create a 403 Forbidden error message.
 * @method static array E404(?string $error = null) Create a 404 Not Found error message.
 * @method static array E409(?string $error = null) Create a 409 Conflict error message.
 * @method static array E429(?string $error = null) Create a 429 Too Many Requests error message.
 * @method static array E500(?string $error = null) Create a 500 Internal Server Error message.
 */
class Messages extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Messages';
    }
}