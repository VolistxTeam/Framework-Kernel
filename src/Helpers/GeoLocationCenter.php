<?php

namespace Volistx\FrameworkKernel\Helpers;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\Facades\HMAC;

class GeoLocationCenter
{
    private Client $client;
    private string $httpBaseUrl;
    private string $remoteToken;
    private string $verification_token;

    public function __construct()
    {
        $this->client = new Client();
        $this->httpBaseUrl = config('volistx.geolocation.base_url');
        $this->remoteToken = config('volistx.geolocation.token');
        $this->verification_token = config('volistx.geolocation.verification');
    }

    public function search(string $ip)
    {
        $url = "$this->httpBaseUrl/lookup?ip=$ip";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
            ],
        ]);

        return $response->getStatusCode() == 200 && HMAC::verify($this->verification_token, 'GET', urlencode($url), $response)
            ? json_decode($response->getBody()->getContents())
            : null;
    }
}
