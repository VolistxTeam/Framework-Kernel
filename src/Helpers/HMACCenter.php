<?php

namespace Volistx\FrameworkKernel\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class HMACCenter
{
    /**
     * Signs the content with HMAC.
     *
     * @param mixed $content The content to sign
     *
     * @return array The HMAC signature headers
     */
    public static function sign(mixed $content): array
    {
        $key = PersonalTokens::getToken()->hmac_token;
        $method = Request::method();
        $url = urlencode(URL::full());
        $timestamp = Carbon::now()->getTimestamp();
        $nonce = Uuid::uuid4()->toString();
        $contentString = json_encode($content);
        $valueToSign = $method
            . $url
            . $nonce
            . $timestamp
            . $contentString;
        $signedValue = hash_hmac('sha256', $valueToSign, $key, true);
        $signature = base64_encode($signedValue);
        return [
            'X-HMAC-Timestamp' => $timestamp,
            'X-HMAC-Content-SHA256' => $signature,
            'X-HMAC-Nonce' => $nonce,
        ];
    }

    /**
     * Verifies the HMAC token.
     *
     * @param string $hmacToken The HMAC token
     * @param string $method The HTTP method
     * @param string $url The URL
     * @param ResponseInterface $response The response object
     *
     * @return bool True if the HMAC token is valid, false otherwise
     */
    public static function verify(string $hmacToken, string $method, string $url, ResponseInterface $response): bool
    {
        $timestamp = $response->getHeader('X-Hmac-Timestamp')[0];
        if (Carbon::now()->greaterThan(Carbon::createFromTimestamp($timestamp)->addSeconds(3))) {
            return false;
        }
        $contentString = $response->getBody()->getContents();
        $nonce = $response->getHeader('X-Hmac-Nonce')[0];
        $valueToSign = $method
            . $url
            . $nonce
            . $timestamp
            . $contentString;
        $signedValue = hash_hmac('sha256', $valueToSign, $hmacToken, true);
        return base64_encode($signedValue) === $response->getHeader('X-Hmac-Content-Sha256')[0];
    }
}