<?php

namespace Volistx\FrameworkKernel\Repositories;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\Repositories\Interfaces\IUserLogRepository;

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
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($inputs),
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindById($log_id)
    {
        $response = $this->client->get("$this->httpBaseUrl/{$log_id}", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindAll($needle, $page, $limit)
    {
        $response = $this->client->get("$this->httpBaseUrl", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            [
                'query' => [
                    'search' => $needle,
                    'page'   => $page,
                    'limit'  => $limit,
                ],
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindSubscriptionLogs($subscription_id, $needle, $page, $limit)
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            [
                'query' => [
                    'search' => $needle,
                    'page'   => $page,
                    'limit'  => $limit,
                ],
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindSubscriptionLogsCount($subscription_id, $date): int
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id/count", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            [
                'query' => [
                    'date' => $date,
                ],
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function FindSubscriptionLogsInMonth($subscription_id, $date)
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id/month", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            [
                'query' => [
                    'date' => $date,
                ],
            ],
        ]);

        return get_object_vars(json_decode($response->getBody()->getContents()));
    }
}
