<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\UserLog;

class UserLogRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return UserLog::query()->create([
            'subscription_id' => $inputs['subscription_id'],
            'url'             => $inputs['url'],
            'ip'              => $inputs['ip'],
            'method'          => $inputs['method'],
            'user_agent'      => $inputs['user_agent'],
        ]);
    }

    public function FindById($log_id): Model|null
    {
        return UserLog::query()->where('id', $log_id)->first();
    }

    public function FindAll($search, $page, $limit): LengthAwarePaginator|null
    {
        //handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('user_logs');

        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return UserLog::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function FindSubscriptionLogs($user_id, $subscription_id, $search, $page, $limit): LengthAwarePaginator|null
    {
        //handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('user_logs');

        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return UserLog::query()
            ->where('user_logs.subscription_id', $subscription_id)
            ->join('subscriptions', 'subscriptions.id', '=', 'user_logs.subscription_id')
            ->where('subscriptions.user_id', $user_id)
            ->select('user_logs.*')
            ->where("user_logs.$values[0]", 'LIKE', "%$searchValue%")
            ->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function FindSubscriptionLogsCountInPeriod($user_id, $subscription_id, $start_date, $end_date): int
    {
        $query = UserLog::query()
            ->where('user_logs.subscription_id', $subscription_id)
            ->join('subscriptions', 'subscriptions.id', '=', 'user_logs.subscription_id')
            ->where('subscriptions.user_id', $user_id)
            ->select('user_logs.*')
            ->whereDate('user_logs.created_at', '>=', $start_date);

        if ($end_date) {
            $query = $query->whereDate('user_logs.created_at', '<=', $end_date);
        }

        return $query->count();
    }

    public function FindSubscriptionUsages($user_id, $subscription_id): ?object
    {
        return UserLog::query()
            ->where('user_logs.subscription_id', $subscription_id)
            ->join('subscriptions', 'subscriptions.id', '=', 'user_logs.subscription_id')
            ->where('subscriptions.user_id', $user_id)
            ->select('user_logs.*')
            ->get()
            ->groupBy(function ($log) {
                return Carbon::parse($log->created_at)->format('d F Y'); // grouping by days
            });
    }
}
