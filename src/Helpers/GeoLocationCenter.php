<?php

namespace Volistx\FrameworkKernel\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class GeoLocationCenter
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function search(string $ip)
    {
        $uniqueCacheID = 'volistx-geolocation-'.$ip;

        $cached = Cache::get($uniqueCacheID);

        if ($cached) {
            return $cached;
        }

        $this->client = new Client([
            'base_uri' => (config('volistx.geolocation.secure') ? 'https' : 'http').'://'.config('volistx.geolocation.base_url').'/',
            'headers'  => [
                'Authorization' => 'Bearer '.config('volistx.geolocation.token'),
                'Content-Type'  => 'application/json',
            ],
        ]);

        // Get data from client
        try {
            $response = $this->client->get('lookup', [
                'ip' => $ip,
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents());

                Cache::put($uniqueCacheID, $data, 60 * 60 * 24 * 5);

                return $data;
            } else {
                return null;
            }
        } catch (GuzzleException $e) {
            return null;
        }
    }
}
