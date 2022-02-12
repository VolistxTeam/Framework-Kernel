<?php

namespace VolistxTeam\VSkeletonKernel\Repositories;

use Illuminate\Support\Facades\Schema;
use VolistxTeam\VSkeletonKernel\Models\AdminLog;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IAdminLogRepository;

class LocalAdminLogRepository implements IAdminLogRepository
{
    public function Create(array $inputs)
    {
        AdminLog::query()->create([
            'access_token_id' => $inputs['access_token_id'],
            'url' => $inputs['url'],
            'ip' => $inputs['ip'],
            'method' => $inputs['method'],
            'user_agent' => $inputs['user_agent'],
        ]);
    }

    public function Find($log_id)
    {
        return AdminLog::query()->where('id', $log_id)->first();
    }

    public function FindAll($needle, $page, $limit)
    {
        $columns = Schema::getColumnListing('admin_logs');
        $query = AdminLog::query();

        foreach ($columns as $column) {
            $query->orWhere("admin_logs.$column", 'LIKE', "%$needle%");
        }
        $logs = $query->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current' => $logs->currentPage(),
                'total' => $logs->lastPage(),
            ],
            'items' => $logs->items()
        ];
    }
}