<?php

namespace VolistxTeam\VSkeletonKernel\Repositories;

use Exception;
use Illuminate\Support\Facades\Schema;
use VolistxTeam\VSkeletonKernel\Models\Plan;

class PlanRepository
{
    public function Create(array $inputs): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return Plan::query()->create([
            'name'        => $inputs['name'],
            'description' => $inputs['description'],
            'data'        => $inputs['data'],
        ]);
    }

    public function Update($plan_id, array $inputs)
    {
        $plan = $this->Find($plan_id);

        if (!$plan) {
            return null;
        }

        $name = $inputs['name'] ?? null;
        $description = $inputs['description'] ?? null;
        $data = $inputs['data'] ?? null;

        if (!$name && !$description && !$data) {
            return $plan;
        }

        if ($name) {
            $plan->name = $name;
        }
        if ($description) {
            $plan->description = $description;
        }
        if ($data) {
            $plan->data = $data;
        }

        $plan->save();

        return $plan;
    }

    public function Find($plan_id): object|null
    {
        return Plan::query()->where('id', $plan_id)->first();
    }

    /**
     * @return false|null|string[]
     *
     * @psalm-return array{result: 'true'}|false|null
     */
    public function Delete($plan_id): array|false|null
    {
        $toBeDeletedPlan = $this->Find($plan_id);

        if (!$toBeDeletedPlan) {
            return null;
        }

        try {
            $toBeDeletedPlan->delete();

            return [
                'result' => 'true',
            ];
        } catch (Exception $ex) {
            return false;
        }
    }

    public function FindAll($needle, int $page, int $limit): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $columns = Schema::getColumnListing('plans');

        return Plan::query()->where(function ($query) use ($needle, $columns) {
            foreach ($columns as $column) {
                $query->orWhere("plans.$column", 'LIKE', "%$needle%");
            }
        })->paginate($limit, ['*'], 'page', $page);
    }
}
