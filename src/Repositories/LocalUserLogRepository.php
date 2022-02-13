<?php

namespace VolistxTeam\VSkeletonKernel\Repositories;

use Illuminate\Support\Facades\Schema;
use VolistxTeam\VSkeletonKernel\Models\UserLog;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IUserLogRepository;

class LocalUserLogRepository implements IUserLogRepository
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
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

    /**
     * @return null|object
     */
    public function FindById($log_id)
    {
        return UserLog::query()->where('id', $log_id)->first();
    }

    /**
     * @return (array|mixed)[]
     *
     * @psalm-return array{pagination: array{per_page: mixed, current: mixed, total: mixed}, items: mixed}
     */
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
                'current'  => $logs->currentPage(),
                'total'    => $logs->lastPage(),
            ],
            'items' => $logs->items,
        ];
    }

    /**
     * @return (array|mixed)[]
     *
     * @psalm-return array{pagination: array{per_page: mixed, current: mixed, total: mixed}, items: mixed}
     */
    public function FindLogsBySubscription($subscription_id, $needle, $page, $limit)
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
                'current'  => $logs->currentPage(),
                'total'    => $logs->lastPage(),
            ],
            'items' => $logs->items,
        ];
    }

    public function FindLogsBySubscriptionCount($subscription_id, $date): int
    {
        return UserLog::query()->where('subscription_id', $subscription_id)
            ->whereMonth('created_at', $date->format('m'))
            ->whereYear('created_at', $date->format('Y'))
            ->count();
    }
}
