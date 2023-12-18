<?php

namespace Volistx\FrameworkKernel\Services;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\DataTransferObjects\AdminLogDTO;
use Volistx\FrameworkKernel\DataTransferObjects\UserLogDTO;
use Volistx\FrameworkKernel\Facades\Requests;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class RemoteAdminLoggingService implements IAdminLoggingService
{
    private string $httpBaseUrl;
    private string $remoteToken;

    public function __construct()
    {
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
        Requests::post(
            "$this->httpBaseUrl/admins/logs",
            $this->remoteToken,
            $inputs
        );
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
        $response = Requests::get("$this->httpBaseUrl/admins/logs/$logId", $this->remoteToken);

        // Retry the job if the request fails
        if ($response->isError) {
            return null;
        }

        return AdminLogDTO::fromModel($response->body)->GetDTO();
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
        $response = Requests::get("$this->httpBaseUrl/admins/logs", $this->remoteToken, [
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
        ]);

        // Retry the job if the request fails
        if ($response->isError) {
            return null;
        }

        $logs = $response->body;

        $logDTOs = [];

        foreach ($logs['items'] as $log) {
            $logDTOs[] = AdminLogDTO::fromModel($log)->getDTO();
        }
        return [
            'pagination' => [
                'per_page' => $logs['pagination']['per_page'],
                'current' => $logs['pagination']['current'],
                'total' => $logs['pagination']['total'],
            ],
            'items' => $logDTOs,
        ];
    }
}
