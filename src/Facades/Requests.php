<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;
use Volistx\FrameworkKernel\Helpers\Requests\ProcessedResponse;

/**
 * Facade for sending HTTP requests.
 *
 * @method static ProcessedResponse Get(string $url, string $token, array $query = []) Send a GET request.
 * @method static ProcessedResponse Post(string $url, string $token, array $query = []) Send a POST request.
 * @method static ProcessedResponse Put(string $url, string $token, array $query = []) Send a PUT request.
 * @method static ProcessedResponse Patch(string $url, string $token, array $query = []) Send a PATCH request.
 * @method static ProcessedResponse Delete(string $url, string $token) Send a DELETE request.
 */
class Requests extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Requests';
    }
}