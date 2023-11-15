<?php

namespace Volistx\FrameworkKernel\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Volistx\FrameworkKernel\DataTransferObjects\UserLogDTO;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;
use Volistx\FrameworkKernel\Repositories\UserLogRepository;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class LocalUserLoggingService implements IUserLoggingService
{
    private UserLogRepository $logRepository;
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(UserLogRepository $logRepository, SubscriptionRepository $subscriptionRepository)
    {
        $this->logRepository = $logRepository;
        $this->subscriptionRepository = $subscriptionRepository;
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
        $this->logRepository->Create($inputs);
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
        $log = $this->logRepository->FindById($logId);

        if ($log === null) {
            return null;
        }

        return UserLogDTO::fromModel($log)->getDTO();
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
    public function GetLogs(string $search, int $page, int $limit): array|null
    {
        $logs = $this->logRepository->FindAll($search, $page, $limit);

        if ($logs === null) {
            return null;
        }

        $logDTOs = [];

        foreach ($logs->items() as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->getDTO();
        }

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }

    /**
     * Get all subscription log entries for a user and subscription with pagination support.
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
        $logs = $this->logRepository->FindSubscriptionLogs($userId, $subscriptionId, $search, $page, $limit);

        if ($logs === null) {
            return [];
        }

        $logDTOs = [];

        foreach ($logs->items() as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->getDTO();
        }

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }

    /**
     * Get the count of subscription log entries for a user and subscription within the plan duration.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return int
     */
    public function GetSubscriptionLogsCountInPlanDuration(string $userId, string $subscriptionId): int
    {
        $subscription = $this->subscriptionRepository->find($userId, $subscriptionId);
        $planDuration = $subscription->plan->data['duration'];
        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $subscription->activated_at);
        $endDate = $startDate->clone()->addDays($planDuration);

        while (!Carbon::now()->between($startDate, $endDate)) {
            $startDate->addDays($planDuration);
            $endDate->addDays($planDuration);
        }

        return $this->logRepository->FindSubscriptionLogsCountInPeriod($userId, $subscriptionId, $startDate, $endDate);
    }

    /**
     * Get the subscription usages for a user and subscription.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return array
     */
    public function GetSubscriptionUsages(string $userId, string $subscriptionId): array
    {
        $daysLogs = $this->logRepository->FindSubscriptionUsages($userId, $subscriptionId);
        $totalCount = 0;
        $stats = [];

        foreach ($daysLogs as $dayLogs) {
            $count = count($dayLogs);
            $totalCount += $count;
            $stats[] = [
                'date' => Carbon::createFromFormat('Y-m-d H:i:s', $dayLogs[0]->created_at)->format('Y-m-d'),
                'count' => $count,
            ];
        }

        $requestsCount = $this->subscriptionRepository->find($userId, $subscriptionId)->plan->data['requests'];

        return [
            'usages' => [
                'current' => $totalCount,
                'max' => (int)$requestsCount,
                'percent' => $requestsCount ? (float)number_format(($totalCount * 100) / $requestsCount, 2) : null,
            ],
            'details' => $stats,
        ];
    }
}