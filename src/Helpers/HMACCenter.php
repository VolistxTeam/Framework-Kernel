<?php

namespace Volistx\FrameworkKernel\Helpers;

class HMACCenter
{
    public static function sign($content, $key): string
    {
        $hashed_content = hash_hmac('sha256', $content, $key, true);
        return base64_encode($hashed_content);
    }
}
