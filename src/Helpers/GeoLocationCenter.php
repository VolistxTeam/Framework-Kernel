<?php

namespace Volistx\FrameworkKernel\Helpers;

use GuzzleHttp\Client;

class GeoLocationCenter
{
    private Client $client;
    private string $httpBaseUrl;
    private string $remoteToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->httpBaseUrl = config('volistx.geoPoint.BaseUrl');
        $this->remoteToken = config('volistx.geoPoint.APIToken');
    }

    public function search(string $ip)
    {
        $response = $this->client->get("$this->httpBaseUrl/$ip", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
            ],
        ]);

        return $response->getStatusCode() == 200 ? json_decode($response->getBody()->getContents()) : null;
    }
}
