<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Volistx\FrameworkKernel\Models\UserLog;
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

    public function FindSubscriptionStats($subscription_id, $date)
    {
        $specifiedDate = Carbon::parse($date);
        $thisDate = Carbon::now();
        $lastDay = $specifiedDate->format('Y-m') == $thisDate->format('Y-m') ? $thisDate->day : (int)$specifiedDate->format('t');


        $logMonth = UserLog::where('subscription_id', $subscription_id)
            ->whereYear('created_at', $specifiedDate->format('Y'))
            ->whereMonth('created_at', $specifiedDate->format('m'))
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('j'); // grouping by days
            })->toArray();

        $totalCount = UserLog::where('subscription_id', $subscription_id)
            ->whereYear('created_at', $specifiedDate->format('Y'))
            ->whereMonth('created_at', $specifiedDate->format('m'))
            ->count();

        $stats = [];

        for ($i = 1; $i <= $lastDay; $i++) {
            $stats[] = [
                'date' => $specifiedDate->format('Y-m-') . sprintf("%02d", $i),
                'count' => isset($logMonth[$i]) ? count($logMonth[$i]) : 0
            ];
        }

        return $stats;
    }

}
