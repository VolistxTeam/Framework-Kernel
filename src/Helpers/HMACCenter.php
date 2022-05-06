<?php

namespace Volistx\FrameworkKernel\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Ramsey\Uuid\Uuid;

class HMACCenter
{
    public static function sign($content, $key): array
    {
        $method = Request::method();
        $url = urlencode(URL::current());
        $timestamp = strtotime('now');
        $nonce = Uuid::uuid4()->toString();
        $contentString = json_encode($content);

        $valueToSign = $method
            .$url
            .$nonce
            .$timestamp
            .$contentString;

        $signedValue = hash_hmac('sha256', $valueToSign, $key, true);

        $signature = base64_encode($signedValue);

        return [
            'X-HMAC-Timestamp'    => $timestamp,
            'X-HMAC-Content-SHA256' => $signature,
            'X-HMAC-Nonce'        => $nonce,
        ];
    }
}
