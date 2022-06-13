<?php

namespace Volistx\FrameworkKernel\Helpers;

class MessagesCenter
{
    public function E400($error = 'One or more invalid parameters were specified.'): array
    {
        return self::Error('InvalidParameter', $error);
    }

    public function Error(string $type, $info): array
    {
        return [
            'error' => [
                'type' => $type,
                'info' => $info,
            ],
        ];
    }

    public function E401($error = 'You have insufficient permissions to access this resource.'): array
    {
        return self::Error('Unauthorized', $error);
    }

    public function E403($error = 'You don\'t have permission to access this resource.'): array
    {
        return self::Error('Forbidden', $error);
    }

    public function E404($error = 'The requested item is not found in the server.'): array
    {
        return self::Error('NotFound', $error);
    }

    public function E409($error = 'The request could not be completed due to a conflict with the current state of the resource.'): array
    {
        return self::Error('Conflict', $error);
    }

    public function E429($error = 'The user has exceeded subscription\'s rate limit.'): array
    {
        return self::Error('RateLimitReached', $error);
    }

    public function E500($error = 'Something went wrong with the server. Please try later.'): array
    {
        return self::Error('Unknown', $error);
    }
}
