<?php

namespace VolistxTeam\VSkeletonKernel\Classes;

class MessagesCenter
{
    public function E400($error = 'One or more invalid fields were specified using the fields parameters.'): array
    {
        return self::Error('xInvalidParameters', $error);
    }

    /**
     * @return array[]
     *
     * @psalm-return array{error: array{type: mixed, info: mixed}}
     */
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
        return self::Error('xUnauthorized', $error);
    }

    public function E403($error = 'Forbidden request.'): array
    {
        return self::Error('xForbidden', $error);
    }

    public function E404($error = 'No item found with provided parameters.'): array
    {
        return self::Error('xNotFound', $error);
    }

    public function E409($error = 'could not be completed due to a conflict with the current state of the resource.'): array
    {
        return self::Error('xConflict', $error);
    }

    public function E429($error = 'Too many requests.'): array
    {
        return self::Error('xManyRequests', $error);
    }

    public function E500($error = 'Something went wrong with the server. Please try later.'): array
    {
        return self::Error('xUnknownError', $error);
    }
}
