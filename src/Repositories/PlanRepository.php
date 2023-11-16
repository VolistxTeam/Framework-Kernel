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
    /**
     * Create a new plan.
     *
     * @param array $inputs [name, tag, description, data, price, tier, custom]
     *
     * @return Model|Builder
     */
    public function Create(array $inputs): Model|Builder
    {
        return Plan::query()->create([
            'name'        => $inputs['name'],
            'tag'         => $inputs['tag'],
            'description' => $inputs['description'],
            'data'        => $inputs['data'],
            'price'       => $inputs['price'],
            'tier'        => $inputs['tier'],
            'custom'      => $inputs['custom'],
            'is_active'   => true,
        ]);
    }

    /**
     * Update an existing plan.
     *
     * @param string $planId
     * @param array  $inputs [name, tag, description, data, price, tier, custom, is_active]
     *
     * @return object|null
     */
    public function Update(string $planId, array $inputs): ?object
    {
        $plan = $this->Find($planId);

        if (!$plan) {
            return null;
        }

        if (array_key_exists('name', $inputs)) {
            $plan->name = $inputs['name'];
        }

        if (array_key_exists('tag', $inputs)) {
            $plan->tag = $inputs['tag'];
        }

        if (array_key_exists('description', $inputs)) {
            $plan->description = $inputs['description'];
        }

        if (array_key_exists('data', $inputs)) {
            $plan->data = $inputs['data'];
        }

        if (array_key_exists('price', $inputs)) {
            $plan->price = $inputs['price'];
        }

        if (array_key_exists('tier', $inputs)) {
            $plan->tier = $inputs['tier'];
        }

        if (array_key_exists('custom', $inputs)) {
            $plan->custom = $inputs['custom'];
        }

        if (array_key_exists('is_active', $inputs)) {
            $plan->is_active = $inputs['is_active'];
        }

        $plan->save();

        return $plan;
    }

    /**
     * Find a plan by ID.
     *
     * @param string $planId
     *
     * @return object|null
     */
    public function Find(string $planId): ?object
    {
        return Plan::query()->where('id', $planId)->first();
    }

    /**
     * Delete a plan by ID.
     *
     * @param string $planId
     *
     * @return bool|null
     */
    public function Delete(string $planId): ?bool
    {
        $toBeDeletedPlan = $this->Find($planId);

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

    /**
     * Find all plans with pagination support.
     *
     * @param string $search
     * @param int    $page
     * @param int    $limit
     *
     * @return LengthAwarePaginator|null
     */
    public function FindAll(string $search, int $page, int $limit): ?LengthAwarePaginator
    {
        // Handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('plans');
        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return Plan::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }
}
