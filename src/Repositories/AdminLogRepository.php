<?php

namespace Volistx\FrameworkKernel\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\AdminLog;

class AdminLogRepository
{
    /**
     * Create a new admin log.
     *
     * @param array $inputs The input data for creating the admin log.
     *
     * @return Model|Builder The created admin log model or builder instance.
     */
    public function Create(array $inputs): Model|Builder
    {
        return AdminLog::query()->create([
            'access_token_id' => $inputs['access_token_id'],
            'url'             => $inputs['url'],
            'ip'              => $inputs['ip'],
            'method'          => $inputs['method'],
            'user_agent'      => $inputs['user_agent'],
        ]);
    }

    /**
     * Find an admin log by ID.
     *
     * @param string $logId The ID of the admin log to find.
     *
     * @return Model|null The found admin log model or null if not found.
     */
    public function Find(string $logId): Model|null
    {
        return AdminLog::query()->where('id', $logId)->first();
    }

    /**
     * Find all admin logs with pagination support.
     *
     * @param string $search The search query.
     * @param int    $page   The page number.
     * @param int    $limit  The number of items per page.
     *
     * @return LengthAwarePaginator|null The paginated admin logs or null if search query is invalid.
     */
    public function FindAll(string $search, int $page, int $limit): LengthAwarePaginator|null
    {
        // Handle empty search query
        if ($search === '') {
            $search = 'id:';
        }

        // Check if search query is in valid format
        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('admin_logs');
        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        // Check if the column name is valid
        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return AdminLog::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->orWhereEncrypted($values[0], 'LIKE', "%$searchValue%")
            ->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }
}
