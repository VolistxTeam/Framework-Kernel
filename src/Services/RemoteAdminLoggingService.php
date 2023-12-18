<?php

namespace Volistx\FrameworkKernel\Services;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\DataTransferObjects\AdminLogDTO;
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

    /**
     * Create a new admin log entry.
     *
     * @param array $inputs
     *
     * @return void
     */
    public function CreateAdminLog(array $inputs): void
    {
        $this->client->post("$this->httpBaseUrl/admins/logs", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($inputs),
        ]);
    }

    /**
     * Get an admin log entry by log ID.
     *
     * @param string $logId
     *
     * @return mixed
     */
    public function GetAdminLog(string $logId): mixed
    {
        $response = $this->client->get("$this->httpBaseUrl/admins/logs/$logId", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            return AdminLogDTO::fromModel(json_decode($response->getBody()->getContents()))->GetDTO();
        }

        return null;
    }

    /**
     * Get all admin log entries with pagination support.
     *
     * @param string $search
     * @param int $page
     * @param int $limit
     *
     * @return array|null
     */
    public function GetAdminLogs(string $search, int $page, int $limit): array|null
    {
        $response = $this->client->get("$this->httpBaseUrl/admins/logs", [
            'headers' => [
                'Authorization' => "Bearer {$this->remoteToken}",
                'Content-Type' => 'application/json',
            ],
            'query' => [
                'search' => $search,
                'page' => $page,
                'limit' => $limit,
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $logs = get_object_vars(json_decode($response->getBody()->getContents()));


            $logDTOs = [];

            foreach ($logs['items'] as $log) {
                $logDTOs[] = AdminLogDTO::fromModel($log)->getDTO();
            }

            return [
                'pagination' => [
                    'per_page' => $logs['perPage'],
                    'current' => $logs['current'],
                    'total' => $logs['total'],
                ],
                'items' => $logDTOs,
            ];
        }

        return null;
    }
}
