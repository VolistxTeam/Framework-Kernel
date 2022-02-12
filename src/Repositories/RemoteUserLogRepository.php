<?php

namespace VolistxTeam\VSkeletonKernel\Repositories;

use GuzzleHttp\Client;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IUserLogRepository;

class RemoteUserLogRepository implements IUserLogRepository
{
    private Client $client;
    private string $httpBaseUrl;
    private string $remoteToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->httpBaseUrl = config('volistx.logging.userLogHttpUrl');
        $this->remoteToken = config('volistx.logging.userLogHttpToken');
    }

    public function Create(array $inputs)
    {
        $response = $this->client->post($this->httpBaseUrl, [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => "application/json"
            ],
            'body' => json_encode($inputs)
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindById($log_id)
    {
        $response = $this->client->get("$this->httpBaseUrl/{$log_id}", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => "application/json"
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindAll($needle, $page, $limit)
    {
        $response = $this->client->get("$this->httpBaseUrl", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => "application/json"
            ],
            [
                'query' => [
                    'search' => $needle,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindLogsBySubscription($subscription_id, $needle, $page, $limit)
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => "application/json"
            ],
            [
                'query' => [
                    'search' => $needle,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindLogsBySubscriptionCount($subscription_id, $date): int
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id/count", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => "application/json"
            ],
            [
                'query' => [
                    'date' => $date,
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents());
    }
}
