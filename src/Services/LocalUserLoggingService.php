<?php

namespace Volistx\FrameworkKernel\Services;

use Carbon\Carbon;
use Volistx\FrameworkKernel\DataTransferObjects\UserLogDTO;
use Volistx\FrameworkKernel\Repositories\UserLogRepository;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;
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

        $logDTOs = [];
        foreach ($logs->items() as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->GetDTO();
        }

        return response()->json([
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ]);
    }


    public function GetSubscriptionLogs($subscription_id, string $search, int $page, int $limit)
    {
        $logs = $this->logRepository->FindSubscriptionLogs($subscription_id, $search, $page, $limit);

        $logDTOs = [];
        foreach ($logs->items() as $log) {
            $logDTOs[] = UserLogDTO::fromModel($log)->GetDTO();
        }

        return response()->json([
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ]);
    }

    public function GetSubscriptionLogsCount($subscription_id, $date)
    {
        return $this->logRepository->FindSubscriptionLogsCount($subscription_id, $date);
    }

    public function GetSubscriptionUsages($subscription_id, $date, $mode)
    {
        $groupedLogs = $this->logRepository->FindSubscriptionUsages($subscription_id, $date);

        $specifiedDate = Carbon::parse($date);
        $thisDate = Carbon::now();
        $lastDay = $specifiedDate->format('Y-m') == $thisDate->format('Y-m') ? $thisDate->day : (int)$specifiedDate->format('t');

        $totalCount = 0;
        $stats = [];
        for ($i = 1; $i <= $lastDay; $i++) {
            $groupedCount = isset($groupedLogs[$i]) ? count($groupedLogs[$i]) : 0;
            if ($mode === 'focused' && $groupedCount === 0) {
                continue;
            }
            $totalCount += $groupedCount;
            $stats[] = [
                'date' => $specifiedDate->format('Y-m-') . sprintf('%02d', $i),
                'count' => $groupedCount,
            ];
        }

        $requestsCount = $this->subscriptionRepository->Find($subscription_id)->plan()->first()->data['requests'];

        return response()->json([
            'usages' => [
                'current' => $totalCount,
                'max' => (int)$requestsCount,
                'percent' => $requestsCount ? (float)number_format(($totalCount * 100) / $requestsCount, 2) : null,
            ],
            'details' => $stats,
        ]);
    }
}