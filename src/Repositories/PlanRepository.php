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
            'price' => $inputs['price'],
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
        $price = $inputs['price'] ?? null;

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

    public function FindAll($search, int $page, int $limit): LengthAwarePaginator|null
    {
        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('plans');

        $values = explode(':', $search, 2);
        $columnName = strtolower($values[0]);

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower($values[1]);

        return Plan::query()
            ->where($values[0], 'LIKE', $searchValue)
            ->paginate($limit, ['*'], 'page', $page);
    }
}
