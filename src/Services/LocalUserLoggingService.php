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

    public function CreateUserLog(array $inputs)
    {
        $this->logRepository->Create($inputs);
    }

    public function GetLog($log_id): Model|array
    {
        $log = $this->logRepository->FindById($log_id);

        return $log ?? UserLogDTO::fromModel($log)->GetDTO();
    }

    public function GetLogs($search, $page, $limit)
    {
        $logs = $this->logRepository->FindAll($search, $page, $limit);

        if (!$logs === null) {
            return $logs;
        }

        $logDTOs = [];
        foreach ($logs->items() as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->GetDTO();
        }

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current'  => $logs->currentPage(),
                'total'    => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }

    public function GetSubscriptionLogs($user_id, $subscription_id, $search, $page, $limit): LengthAwarePaginator|array|null
    {
        $logs = $this->logRepository->FindSubscriptionLogs($user_id, $subscription_id, $search, $page, $limit);

        if ($logs === null) {
            return [];
        }

        $logDTOs = [];
        foreach ($logs->items() as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->GetDTO();
        }

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current'  => $logs->currentPage(),
                'total'    => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }

    public function GetSubscriptionLogsCountInPlanDuration($user_id, $subscription_id): int
    {
        $subscription = $this->subscriptionRepository->Find($user_id, $subscription_id);
        $planDuration = $subscription->plan->data['duration'];

        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $subscription->activated_at);
        $end_date = $start_date->addDays($planDuration);

        while (!Carbon::now()->betweenIncluded($start_date, $end_date)) {
            $start_date = $end_date;
            $end_date = $end_date->addDays($planDuration);
        }

        return $this->logRepository->FindSubscriptionLogsCountInPeriod($user_id, $subscription_id, $start_date, $end_date);
    }

    // This function requires rebuilding. discuss.
    public function GetSubscriptionUsages($user_id, $subscription_id): array
    {
        $daysLogs = $this->logRepository->FindSubscriptionUsages($user_id, $subscription_id);

        $totalCount = 0;
        $stats = [];

        foreach ($daysLogs as $dayLogs) {
            $count = count($dayLogs);
            $totalCount += $count;
            $stats[] = [
                'date'  => Carbon::createFromFormat('Y-m-d H:i:s', $dayLogs[0]->created_at)->format('Y-m-d'),
                'count' => $count,
            ];
        }

        $requestsCount = $this->subscriptionRepository->Find($user_id, $subscription_id)->plan->data['requests'];

        return [
            'usages' => [
                'current' => $totalCount,
                'max'     => (int) $requestsCount,
                'percent' => $requestsCount ? (float) number_format(($totalCount * 100) / $requestsCount, 2) : null,
            ],
            'details' => $stats,
        ];
    }
}
