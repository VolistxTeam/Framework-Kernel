<?php

namespace Volistx\FrameworkKernel\Helpers;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\Facades\HMAC;

class GeoLocationCenter
{
    private Client $client;
    private string $httpBaseUrl;
    private string $remoteToken;
    private string $verification_key;

    public function __construct()
    {
        $this->client = new Client();
        $this->httpBaseUrl = config('volistx.geolocation.base_url');
        $this->remoteToken = config('volistx.geolocation.token');
        $this->verification_key = config('volistx.geolocation.verification_key');
    }

    public function search(string $ip)
    {
        $url = "$this->httpBaseUrl/lookup?ip=$ip";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
            ],
        ]);

        if ($this->verification_key) {
            return $response->getStatusCode() == 200 && HMAC::verify($this->verification_key, 'GET', urlencode($url), $response)
                ? json_decode($response->getBody()->getContents())
                : null;
        } else {
            return $response->getStatusCode() == 200 ? json_decode($response->getBody()->getContents()) : null;
        }
    }
}
