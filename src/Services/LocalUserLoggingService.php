<?php

namespace Volistx\FrameworkKernel\Services;

use Carbon\Carbon;
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

    public function GetLog($log_id)
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
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }

    public function GetSubscriptionLogs($subscription_id)
    {
        $subscription = $this->subscriptionRepository->Find($subscription_id);

        $logs = $this->logRepository->FindSubscriptionLogs($subscription_id, $subscription->activated_at, $subscription->expires_at);

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
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }

    public function GetSubscriptionLogsCount($subscription_id)
    {
        $subscription = $this->subscriptionRepository->Find($subscription_id);

        return $this->logRepository->FindSubscriptionLogsCount($subscription_id, $subscription->activated_at, $subscription->expires_at);
    }

    // This function requires rebuilding. discuss.
    public function GetSubscriptionUsages($subscription_id)
    {
        $subscription = $this->subscriptionRepository->Find($subscription_id);

        $daysLogs = $this->logRepository->FindSubscriptionUsages($subscription_id, $subscription->activated_at, $subscription->expires_at);

        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $subscription->activated_at);
        $end_date = $subscription->expired_at === null ? Carbon::now() : Carbon::createFromFormat('Y-m-d H:i:s', $subscription->expires_at);


        $totalCount = 0;
        $stats = [];


        foreach ($daysLogs as $dayLogs) {
            $count = count($dayLogs);
            $totalCount += $count;
            $stats[] = [
                'date' => Carbon::createFromFormat('Y-m-d H:i:s', $dayLogs[0]->created_at)->format('Y-m-d'),
                'count' =>  $count,
            ];
        }

        $requestsCount = $this->subscriptionRepository->Find($subscription_id)->plan->data['requests'];

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
