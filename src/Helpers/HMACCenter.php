<?php

namespace Volistx\FrameworkKernel\Helpers;

class HMACCenter
{
    public static function sign($content, $key): string
    {
        $hashed_content = hash_hmac('sha256', json_encode($content), $key, true);

        return base64_encode($hashed_content);
    }

    public static function getHeaders($content, $key): array
    {
        return [
            'X-HMAC-Timestamp'    => strtotime('now'),
            'X-HMAC-Content-Hash' => self::sign($content, $key),
        ];
    }
}
