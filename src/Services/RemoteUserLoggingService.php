<?php

namespace Volistx\FrameworkKernel\Services;

use GuzzleHttp\Client;
use Volistx\FrameworkKernel\DataTransferObjects\AdminLogDTO;
use Volistx\FrameworkKernel\DataTransferObjects\UserLogDTO;
use Volistx\FrameworkKernel\Facades\Requests;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class RemoteUserLoggingService implements IUserLoggingService
{
    private string $httpBaseUrl;
    private string $remoteToken;

    public function __construct()
    {
        $this->httpBaseUrl = config('volistx.logging.userLogHttpUrl');
        $this->remoteToken = config('volistx.logging.userLogHttpToken');
    }

    /**
     * Create a new user log entry.
     *
     * @param array $inputs [log_id, log_data, log_type]
     *
     * @return void
     */
    public function CreateUserLog(array $inputs): void
    {
        Requests::post(
            "$this->httpBaseUrl/users/logs",
            $this->remoteToken,
            $inputs
        );
    }

    /**
     * Get a user log entry by log ID.
     *
     * @param string $logId
     *
     * @return mixed
     */
    public function GetLog(string $logId): mixed
    {
        $response = Requests::get("$this->httpBaseUrl/users/logs/$logId", $this->remoteToken);

        // Retry the job if the request fails
        if ($response->isError) {
            return null;
        }

        return UserLogDTO::fromModel($response->body)->GetDTO();
    }

    /**
     * Get all user log entries with pagination support.
     *
     * @param string $search
     * @param int $page
     * @param int $limit
     *
     * @return array|null
     */
    public function GetLogs(string $search, int $page, int $limit): ?array
    {
        $response = Requests::get("$this->httpBaseUrl/users/logs", $this->remoteToken, [
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
            $logDTOs[] = UserLogDTO::fromModel($log)->getDTO();
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

    /**
     * Get all subscription log entries for a subscription with pagination support.
     *
     * @param string $userId
     * @param string $subscriptionId
     * @param string $search
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function GetSubscriptionLogs(string $userId, string $subscriptionId, string $search, int $page, int $limit): array
    {
        $response = Requests::get("$this->httpBaseUrl/users/$userId/subscriptions/$subscriptionId", $this->remoteToken, [
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
        ]);

        // Retry the job if the request fails
        if ($response->isError) {
            return [];
        }


        $logs = get_object_vars($response->body);


        $logDTOs = [];

        foreach ($logs['items'] as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->getDTO();
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

    /**
     * Get the count of subscription log entries for a subscription.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return int
     */
    public function GetSubscriptionLogsCountInPlanDuration(string $userId, string $subscriptionId): int
    {
        $response = Requests::get("$this->httpBaseUrl/users/$userId/subscriptions/$subscriptionId/count", $this->remoteToken);
        if ($response->isError) {
            return 0;
        }

        return $response->body;
    }

    /**
     * Get the subscription usages for a subscription.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return array
     */
    public function GetSubscriptionUsages(string $userId, string $subscriptionId): array
    {
        $subscriptionRepo = new SubscriptionRepository();

        $response = Requests::get("$this->httpBaseUrl/users/$userId/subscriptions/$subscriptionId/usages", $this->remoteToken, [
            'count' => $subscriptionRepo->Find($userId, $subscriptionId)->plan->data['requests'],
        ]);

        if ($response->isError) {
            return [];
        }

        return get_object_vars($response->body);
    }
}
