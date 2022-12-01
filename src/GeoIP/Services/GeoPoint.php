<?php

namespace Volistx\FrameworkKernel\GeoIP\Services;

use Exception;
use Torann\GeoIP\Services\AbstractService;
use Torann\GeoIP\Support\HttpClient;

class GeoPoint extends AbstractService
{
    /**
     * Http client instance.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        $this->client = new HttpClient([
            'base_uri' => ($this->config('secure') ? 'https' : 'http') . '://geopoint.api.volistx.io/',
            'query' => [
                'some_option' => $this->config('some_option'),
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config('key'),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function locate($ip)
    {
        // Get data from client
        $data = $this->client->get('lookup', [
            'ip' => $ip,
        ]);

        // Verify server response
        if ($this->client->getErrors() !== null) {
            throw new Exception('Request failed (' . $this->client->getErrors() . ')');
        }

        return json_decode($data[0]) ?? null;
    }
}