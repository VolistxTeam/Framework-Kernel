<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;
use Psr\Http\Message\ResponseInterface;

/**
 * Facade for accessing the HMAC service.
 *
 * @method static array sign(mixed $content) Signs the content with HMAC and returns the HMAC signature headers.
 * @method static bool verify(string $hmacToken, string $method, string $url, ResponseInterface $response) Verifies the HMAC token.
 */
class HMAC extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'HMAC';
    }
}