<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\UserLog;
use Volistx\FrameworkKernel\Repositories\Interfaces\IUserLogRepository;

class LocalUserLogRepository implements IUserLogRepository
{
    public function Create(array $inputs)
    {
        return UserLog::query()->create([
            'subscription_id' => $inputs['subscription_id'],
            'url' => $inputs['url'],
            'ip' => $inputs['ip'],
            'method' => $inputs['method'],
            'user_agent' => $inputs['user_agent'],
        ]);
    }

    public function FindById($log_id)
    {
        return UserLog::query()->where('id', $log_id)->first();
    }

    public function FindAll($needle, $page, $limit)
    {
        $columns = Schema::getColumnListing('user_logs');

        $logs = UserLog::where(function ($query) use ($columns, $needle) {
            foreach ($columns as $column) {
                $query->orWhere("$column", 'LIKE', "%$needle%");
            }
        })->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logs->items(),
        ];
    }

    public function FindSubscriptionLogs($subscription_id, $needle, $page, $limit)
    {
        $columns = Schema::getColumnListing('user_logs');

        $logs = UserLog::where('subscription_id', $subscription_id)->where(function ($query) use ($columns, $needle) {
            foreach ($columns as $column) {
                $query->orWhere("$column", 'LIKE', "%$needle%");
            }
        })->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logs->items(),
        ];
    }

    public function FindSubscriptionLogsCount($subscription_id, $date): int
    {
        return UserLog::query()->where('subscription_id', $subscription_id)
            ->whereMonth('created_at', $date->format('m'))
            ->whereYear('created_at', $date->format('Y'))
            ->count();
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