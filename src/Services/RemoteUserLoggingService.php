<?php

namespace Volistx\FrameworkKernel\Services;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class RemoteUserLoggingService implements IUserLoggingService
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

    public function CreateUserLog(array $inputs)
    {
        $this->client->post($this->httpBaseUrl, [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($inputs),
        ]);
    }

    public function GetLog($log_id)
    {
        $response = $this->client->get("$this->httpBaseUrl/{$log_id}", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
        ]);

        return $response->getStatusCode() == 200 ? json_decode($response->getBody()->getContents()) : null;
    }

    public function GetLogs($needle, $page, $limit)
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

        return $response->getStatusCode() == 200 ? get_object_vars(json_decode($response->getBody()->getContents())) : null;
    }

    public function GetSubscriptionLogs($subscription_id)
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            [
                'query' => [
                    //                    'search' => $search,
                    //                    'page'   => $page,
                    //                    'limit'  => $limit,
                ],
            ],
        ]);

        return $response->getStatusCode() == 200 ? get_object_vars(json_decode($response->getBody()->getContents())) : null;
    }

    public function GetSubscriptionLogsCount($subscription_id)
    {
        $response = $this->client->get("$this->httpBaseUrl/$subscription_id/count", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            [
                'query' => [
                    //                    'date' => $date,
                ],
            ],
        ]);

        return $response->getStatusCode() == 200 ? json_decode($response->getBody()->getContents()) : null;
    }

    public function GetSubscriptionUsages($subscription_id, $mode)
    {
        $subscriptionRepo = new SubscriptionRepository();

        $response = $this->client->get("$this->httpBaseUrl/$subscription_id/usages", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type'  => 'application/json',
            ],
            'query' => [
                //                'date'  => $date,
                //                'mode'  => $mode,
                //                'count' => $subscriptionRepo->Find($subscription_id)->plan()->first()->data['requests'],
            ],
        ]);

        return $response->getStatusCode() == 200 ? get_object_vars(json_decode($response->getBody()->getContents())) : null;
    }
}
