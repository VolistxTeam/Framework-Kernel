<?php

namespace Volistx\FrameworkKernel\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class HMACCenter
{
    public static function sign($content): array
    {
        $key = PersonalTokens::getToken()->hmac_token;
        $method = Request::method();
        $url = urlencode(URL::full());
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

    public static function verify($hmac_token, $method, $url, ResponseInterface $response)
    {
        $timestamp = $response->getHeader('X-Hmac-Timestamp')[0];

        if (Carbon::now()->greaterThan(Carbon::createFromTimestamp($timestamp)->addSeconds(3))) {
            return false;
        }

        $contentString = $response->getBody()->getContents();
        $nonce = $response->getHeader('X-Hmac-Nonce')[0];

        $valueToSign = $method
            .$url
            .$nonce
            .$timestamp
            .$contentString;

        $signedValue = hash_hmac('sha256', $valueToSign, $hmac_token, true);

        return base64_encode($signedValue) === $response->getHeader('X-Hmac-Content-Sha256')[0];
    }
}
