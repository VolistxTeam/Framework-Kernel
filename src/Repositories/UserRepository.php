<?php

namespace Volistx\FrameworkKernel\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;
use Volistx\FrameworkKernel\Models\User;

class UserRepository
{
    /**
     * Create a new user.
     *
     * @param array $inputs [user_id]
     *
     * @return Model|Builder
     */
    public function Create(array $inputs): Model|Builder
    {
        return User::query()->create([
            'id'        => $inputs['user_id'] ?? Uuid::uuid4(),
            'is_active' => true,
        ]);
    }

    /**
     * Update an existing user.
     *
     * @param string $userId
     * @param array  $inputs [is_active]
     *
     * @return object|null
     */
    public function Update(string $userId, array $inputs): ?object
    {
        $user = $this->Find($userId);

        if (!$user) {
            return null;
        }

        if (array_key_exists('is_active', $inputs)) {
            $user->is_active = $inputs['is_active'];
        }

        $user->save();

        return $user;
    }

    /**
     * Find a user by ID.
     *
     * @param string $userId
     *
     * @return object|null
     */
    public function Find(string $userId): ?object
    {
        return User::query()->where('id', $userId)->first();
    }

    /**
     * Delete a user by ID.
     *
     * @param string $userId
     *
     * @return bool|null
     */
    public function Delete(string $userId): ?bool
    {
        $toBeDeletedUser = $this->find($userId);

        if (!$toBeDeletedUser) {
            return null;
        }

        try {
            $toBeDeletedUser->delete();

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Find all users with pagination support.
     *
     * @param string $search
     * @param int    $page
     * @param int    $limit
     *
     * @return LengthAwarePaginator|null
     */
    public function FindAll(string $search, int $page, int $limit): LengthAwarePaginator|null
    {
        // Handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('users');
        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return User::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }
}
