<?php

namespace Volistx\FrameworkKernel\Classes;

class MessagesCenter
{
    public function E400($error = 'One or more invalid parameters were specified.'): array
    {
        return self::Error('invalid_parameters', $error);
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

    public function E401($error = 'Insufficient permissions to perform this request.'): array
    {
        return self::Error('unauthorized', $error);
    }

    public function E403($error = 'Forbidden request.'): array
    {
        return self::Error('forbidden', $error);
    }

    public function E404($error = 'No item found with provided parameters.'): array
    {
        return self::Error('not_found', $error);
    }

    public function E409($error = 'Could not be completed due to a conflict with the current state of the resource.'): array
    {
        return self::Error('conflict', $error);
    }

    public function E429($error = 'User has reached subscription plan's rate limit.'): array
    {
        return self::Error('ratelimit_reached', $error);
    }

    public function E500($error = 'Something went wrong with the server. Please try later.'): array
    {
        return self::Error('unknown', $error);
    }
}
