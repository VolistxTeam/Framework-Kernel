<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\UserLog;

class UserLogRepository
{
    public function Create(array $inputs)
    {
        return UserLog::query()->create([
            'subscription_id' => $inputs['subscription_id'],
            'url'             => $inputs['url'],
            'ip'              => $inputs['ip'],
            'method'          => $inputs['method'],
            'user_agent'      => $inputs['user_agent'],
        ]);
    }

    public function FindById($log_id)
    {
        return UserLog::query()->where('id', $log_id)->first();
    }

    public function FindAll($search, $page, $limit)
    {
        $columns = Schema::getColumnListing('user_logs');

        return UserLog::where(function ($query) use ($columns, $search) {
            foreach ($columns as $column) {
                $query->orWhere("$column", 'LIKE', "%$search%");
            }
        })->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function FindSubscriptionLogs($subscription_id, $needle, $page, $limit)
    {
        $columns = Schema::getColumnListing('user_logs');

        return UserLog::where('subscription_id', $subscription_id)->where(function ($query) use ($columns, $needle) {
            foreach ($columns as $column) {
                $query->orWhere("$column", 'LIKE', "%$needle%");
            }
        })->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function FindSubscriptionLogsCount($subscription_id, $date)
    {
        return UserLog::query()->where('subscription_id', $subscription_id)
            ->whereMonth('created_at', $date->format('m'))
            ->whereYear('created_at', $date->format('Y'))
            ->count();
    }

    public function FindSubscriptionUsages($subscription_id, $date)
    {
        return UserLog::where('subscription_id', $subscription_id)
            ->whereYear('created_at', Carbon::parse($date)->format('Y'))
            ->whereMonth('created_at', Carbon::parse($date)->format('m'))
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('j'); // grouping by days
            });
    }
}
