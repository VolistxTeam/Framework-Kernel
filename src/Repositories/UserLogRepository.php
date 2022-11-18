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

    public function FindSubscriptionLogs($user_id, $start_date, $end_date): LengthAwarePaginator|null
    {
        $start = Carbon::createFromFormat('date:Y-m-d H:i:s', $start_date);
        $query = UserLog::query()->where('user_id', $user_id)
            ->whereDate('created_at', '>=', $start);

        if ($end_date != null) {
            $end = Carbon::createFromFormat('date:Y-m-d H:i:s', $end_date);
            $query = $query->whereDate('created_at', '<=', $end);
        }

        return $query->get();
    }

    public function FindSubscriptionLogsCount($user_id, $start_date, $end_date): int
    {
        $start = Carbon::createFromFormat('date:Y-m-d H:i:s', $start_date);
        $query = UserLog::query()->where('user_id', $user_id)
            ->whereDate('created_at', '>=', $start);

        if ($end_date != null) {
            $end = Carbon::createFromFormat('date:Y-m-d H:i:s', $end_date);
            $query = $query->whereDate('created_at', '<=', $end);
        }

        return $query->count();
    }

    public function FindSubscriptionUsages($user_id, $start_date, $end_date): ?object
    {
        $start = Carbon::createFromFormat('date:Y-m-d H:i:s', $start_date);
        $query = UserLog::query()->where('user_id', $user_id)
            ->whereDate('created_at', '>=', $start);

        if ($end_date != null) {
            $end = Carbon::createFromFormat('date:Y-m-d H:i:s', $end_date);
            $query = $query->whereDate('created_at', '<=', $end);
        }

        return $query->get()
            ->groupBy(function ($log) {
                return Carbon::parse($log->created_at)->format('j'); // grouping by days
            });
    }
}
