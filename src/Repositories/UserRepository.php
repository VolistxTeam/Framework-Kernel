<?php

namespace Volistx\FrameworkKernel\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\User;

class UserRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return User::query()->create([
            'id'        => $inputs['id'],
            'is_active' => true,
        ]);
    }

    public function Update($user_id, array $inputs): ?object
    {
        $user = $this->Find($user_id);

        if (!$user) {
            return null;
        }

        if (array_key_exists('is_active', $inputs)) {
            $user->is_active = $inputs['is_active'];
        }

        $user->save();

        return $user;
    }

    public function Find($user_id): ?object
    {
        return User::query()->where('id', $user_id)->first();
    }

    public function Delete($user_id): ?bool
    {
        $toBeDeletedUser = $this->Find($user_id);

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

    public function FindAll($search, int $page, int $limit): LengthAwarePaginator|null
    {
        //handle empty search
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
