<?php

namespace Volistx\FrameworkKernel\Services\Interfaces;

use Exception;
use Torann\GeoIP\Services\AbstractService;
use Torann\GeoIP\Support\HttpClient;

/**
 * Class GeoIP
 * @package Torann\GeoIP\Services
 */
class GeoPoint extends AbstractService
{
    /**
     * Http client instance.
     *ß
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
            'base_uri' => 'https://geopoint.api.volistx.io/',
            'headers' => [
                'User-Agent' => 'Laravel-GeoIP-Torann',
                'Authorization' => 'Bearer ' . config('key'),
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function locate($ip)
    {
        // Get data from client
        $data = $this->client->get($ip);

        // Verify server response
        if ($this->client->getErrors() !== null || empty($data[0])) {
            throw new Exception('Request failed (' . $this->client->getErrors() . ')');
        }

        $json = json_decode($data[0], true);

        return $this->hydrate($json);
    }
}