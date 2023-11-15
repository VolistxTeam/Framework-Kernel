<?php

namespace Volistx\FrameworkKernel\Helpers;

class MessagesCenter
{
    /**
     * Creates an error message.
     *
     * @param string $type The type of the error
     * @param string $info The information about the error
     *
     * @return array The error message
     */
    public function Error(string $type, string $info): array
    {
        return [
            'error' => [
                'type' => $type,
                'info' => $info,
            ],
        ];
    }

    /**
     * Creates a 400 Bad Request error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E400(?string $error = null): array
    {
        return self::Error('InvalidParameter', $error ?? trans('volistx::error.e400'));
    }

    /**
     * Creates a 401 Unauthorized error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E401(?string $error = null): array
    {
        return self::Error('Unauthorized', $error ?? trans('volistx::error.e401'));
    }

    /**
     * Creates a 403 Forbidden error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E403(?string $error = null): array
    {
        return self::Error('Forbidden', $error ?? trans('volistx::error.e403'));
    }

    /**
     * Creates a 404 Not Found error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E404(?string $error = null): array
    {
        return self::Error('NotFound', $error ?? trans('volistx::error.e404'));
    }

    /**
     * Creates a 409 Conflict error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E409(?string $error = null): array
    {
        return self::Error('Conflict', $error ?? trans('volistx::error.e409'));
    }

    /**
     * Creates a 429 Too Many Requests error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E429(?string $error = null): array
    {
        return self::Error('RateLimitReached', $error ?? trans('volistx::error.e429'));
    }

    /**
     * Creates a 500 Internal Server Error message.
     *
     * @param string|null $error The specific error message (default: null)
     *
     * @return array The error message
     */
    public function E500(?string $error = null): array
    {
        return self::Error('Unknown', $error ?? trans('volistx::error.e500'));
    }
}
