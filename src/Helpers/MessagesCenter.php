<?php

namespace Volistx\FrameworkKernel\Helpers;

class MessagesCenter
{
    public function Error($type, $info): array
    {
        return [
            'error' => [
                'type' => $type,
                'info' => $info,
            ],
        ];
    }

    public function E400($error = null): array
    {
        return self::Error('InvalidParameter', $error ?? trans('volistx::error.e400'));
    }

    public function E401($error = null): array
    {
        return self::Error('Unauthorized', $error ?? trans('volistx::error.e401'));
    }

    public function E403($error = null): array
    {
        return self::Error('Forbidden', $error ?? trans('volistx::error.e403'));
    }

    public function E404($error = null): array
    {
        return self::Error('NotFound', $error ?? trans('volistx::error.e404'));
    }

    public function E409($error = null): array
    {
        return self::Error('Conflict', $error ?? trans('volistx::error.e409'));
    }

    public function E429($error = null): array
    {
        return self::Error('RateLimitReached', $error ?? trans('volistx::error.e429'));
    }

    public function E500($error = null): array
    {
        return self::Error('Unknown', $error ?? trans('volistx::error.e500'));
    }
}
