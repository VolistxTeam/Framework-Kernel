<?php

namespace Volistx\FrameworkKernel\Services;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class RemoteAdminLoggingService implements IAdminLoggingService
{
    private Client $client;
    private string $httpBaseUrl;
    private string $remoteToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->httpBaseUrl = config('volistx.logging.adminLogHttpUrl');
        $this->remoteToken = config('volistx.logging.adminLogHttpToken');
    }

    public function CreateAdminLog(array $inputs)
    {
        $this->client->post($this->httpBaseUrl, [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($inputs),
        ]);
    }

    public function GetAdminLog($log_id)
    {
        $response = $this->client->get("$this->httpBaseUrl/{$log_id}", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => 'application/json',
            ],
        ]);

        return $response->getStatusCode() ==200?  json_decode($response->getBody()->getContents()) : null;
    }

    public function GetAdminLogs(string $search, int $page, int $limit)
    {
        $response = $this->client->get("$this->httpBaseUrl", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => 'application/json',
            ],
            [
                'query' => [
                    'search' => $search,
                    'page' => $page,
                    'limit' => $limit,
                ],
            ],
        ]);

        return $response->getStatusCode() ==200?  get_object_vars(json_decode($response->getBody()->getContents())) : null;
    }

}