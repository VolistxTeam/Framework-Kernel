<?php

namespace Volistx\FrameworkKernel\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\Plan;

class PlanRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return Plan::query()->create([
            'name' => $inputs['name'],
            'description' => $inputs['description'],
            'data' => $inputs['data'],
            'price' => $inputs['price']
        ]);
    }

    public function Update($plan_id, array $inputs): ?object
    {
        $plan = $this->Find($plan_id);

        if (!$plan) {
            return null;
        }

        $name = $inputs['name'] ?? null;
        $description = $inputs['description'] ?? null;
        $data = $inputs['data'] ?? null;
        $price = $inputs['price']?? null;

        if ($name !== null) {
            $plan->name = $name;
        }
        if ($description !== null) {
            $plan->description = $description;
        }
        if ($data !== null) {
            $plan->data = $data;
        }
        if ($price !== null) {
            $plan->price = $price;
        }

        $plan->save();

        return $plan;
    }

    public function Find($plan_id): ?object
    {
        return Plan::query()->where('id', $plan_id)->first();
    }

    public function Delete($plan_id): ?bool
    {
        $toBeDeletedPlan = $this->Find($plan_id);

        if (!$toBeDeletedPlan) {
            return null;
        }

        try {
            $toBeDeletedPlan->delete();

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function FindAll($needle, int $page, int $limit): LengthAwarePaginator
    {
        $columns = Schema::getColumnListing('plans');

        return Plan::query()->where(function ($query) use ($needle, $columns) {
            foreach ($columns as $column) {
                $query->orWhere("plans.$column", 'LIKE', "%$needle%");
            }
        })->paginate($limit, ['*'], 'page', $page);
    }
}
