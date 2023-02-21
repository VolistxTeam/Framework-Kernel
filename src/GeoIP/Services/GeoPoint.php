<?php

namespace Volistx\FrameworkKernel\GeoIP\Services;

use Exception;
use InteractionDesignFoundation\GeoIP\Services\AbstractService;
use InteractionDesignFoundation\GeoIP\Support\HttpClient;

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
            'base_uri' => ($this->config('secure') ? 'https' : 'http').'://'.$this->config('base_uri', 'geopoint.api.volistx.io').'/',
            'headers'  => [
                'Authorization' => 'Bearer '.$this->config('key'),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function locate($ip)
    {
        // Get data from client
        $data = $this->client->get('lookup', [
            'ip' => $ip,
        ]);

        // Verify server response
        if ($this->client->getErrors() !== null || empty($data[0])) {
            throw new Exception('Request failed ('.$this->client->getErrors().')');
        }

        $json = json_decode($data[0], true);

        return $this->hydrate([
            'ip'          => $ip,
            'iso_code'    => $json->country->code,
            'country'     => $json->country->name,
            'city'        => $json->city->name,
            'state'       => $json->region->code,
            'state_name'  => $json->region->name,
            'postal_code' => $json->postal_code,
            'lat'         => $json->location->latitude,
            'lon'         => $json->location->longitude,
            'timezone'    => $json->timezone->id,
            'continent'   => $json->continent->code,
        ]);
    }
}
