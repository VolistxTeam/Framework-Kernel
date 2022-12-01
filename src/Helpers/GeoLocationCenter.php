<?php

namespace Volistx\FrameworkKernel\Helpers;

use GuzzleHttp\Client;

class GeoLocationCenter
{
    public function __construct()
    {
        $this->client = new Client();
    }

    public function search(string $ip)
    {
        return geoip($ip);
    }
}
