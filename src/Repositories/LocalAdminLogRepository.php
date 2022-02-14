<?php

namespace Volistx\FrameworkKernel\Repositories;

use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\AdminLog;
use Volistx\FrameworkKernel\Repositories\Interfaces\IAdminLogRepository;

class LocalAdminLogRepository implements IAdminLogRepository
{
    /**
     * @return void
     */
    public function Create(array $inputs)
    {
        AdminLog::query()->create([
            'access_token_id' => $inputs['access_token_id'],
            'url'             => $inputs['url'],
            'ip'              => $inputs['ip'],
            'method'          => $inputs['method'],
            'user_agent'      => $inputs['user_agent'],
        ]);
    }

    /**
     * @return null|object
     */
    public function Find($log_id)
    {
        return AdminLog::query()->where('id', $log_id)->first();
    }

    /**
     * @return array[]
     *
     * @psalm-return array{pagination: array{per_page: int, current: int, total: int}, items: array}
     */
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
                'current'  => $logs->currentPage(),
                'total'    => $logs->lastPage(),
            ],
            'items' => $logs->items(),
        ];
    }
}
