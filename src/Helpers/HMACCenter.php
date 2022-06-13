<?php

namespace Volistx\FrameworkKernel\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Ramsey\Uuid\Uuid;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class HMACCenter
{
    public static function sign($content): array
    {
        $key = PersonalTokens::getToken()->subscription()->first()->hmac_token;
        $method = Request::method();
        $url = urlencode(URL::current());
        $timestamp = Carbon::now()->getTimestamp();
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
            'X-HMAC-Timestamp'      => $timestamp,
            'X-HMAC-Content-SHA256' => $signature,
            'X-HMAC-Nonce'          => $nonce,
        ];
    }
}
