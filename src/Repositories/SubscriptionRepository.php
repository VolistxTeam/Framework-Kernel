<?php

namespace Volistx\FrameworkKernel\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Models\Subscription;

class SubscriptionRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return Subscription::query()->create([
            'user_id'             => $inputs['user_id'],
            'plan_id'             => $inputs['plan_id'],
            'hmac_token'          => $inputs['hmac_token'],
            'plan_activated_at'   => $inputs['plan_activated_at'],
            'plan_expires_at'     => $inputs['plan_expires_at'] ?? null,
            'plan_cancels_at'     => null,
            'plan_cancelled_at'   => null,
        ]);
    }

    public function Update($subscriptionID, array $inputs): ?object
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        $plan_activated_at = $inputs['plan_activated_at'] ?? null;
        $plan_expires_at = $inputs['plan_expires_at'] ?? 1;
        $plan_cancels_at = $inputs['plan_cancels_at'] ?? null;
        $plan_cancelled_at = $inputs['plan_cancelled_at'] ?? null;
        $plan_id = $inputs['plan_id'] ?? null;
        $hmac_token = $inputs['hmac_token'] ?? null;

        if ($plan_id !== null) {
            $subscription->plan_id = $plan_id;
        }

        if ($hmac_token !== null) {
            $subscription->hmac_token = $hmac_token;
        }

        if ($plan_activated_at !== null) {
            $subscription->plan_activated_at = $plan_activated_at;
        }

        if ($plan_expires_at !== 1) {
            $subscription->plan_expires_at = $plan_expires_at;
        }

        if ($plan_cancels_at !== null) {
            $subscription->plan_cancels_at = $plan_cancels_at;
        }

        if ($plan_cancelled_at !== null) {
            $subscription->plan_cancelled_at = $plan_cancelled_at;
        }

        $subscription->save();

        return $subscription;
    }

    public function Find($subscriptionID): ?object
    {
        return Subscription::with('plan')->where('id', $subscriptionID)->first();
    }

    public function Cancel($subscriptionID, $cancels_at, $immediately = false): ?object
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        if ($immediately) {
            $subscription->plan_expires_at = $cancels_at;
        }

        $subscription->plan_cancels_at = $subscription->plan_expires_at;
        $subscription->plan_cancelled_at = $cancels_at;

        $subscription->save();

        return $subscription;
    }

    public function Uncancel($subscriptionID): ?object
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        $subscription->plan_cancels_at = null;
        $subscription->plan_cancelled_at = null;

        $subscription->save();

        return $subscription;
    }

    public function SwitchToFreePlan($subscriptionID): ?object
    {
        if (config('volistx.fallback_plan.id') !== null) {
            $subscription = $this->Find($subscriptionID);

            if (!$subscription) {
                return null;
            }

            $subscription->plan_id = config('volistx.fallback_plan.id');
            $subscription->plan_expires_at = null;

            $subscription->save();

            return $subscription;
        } else {
            return null;
        }
    }

    public function Delete($subscriptionID): ?bool
    {
        $toBeDeletedSub = $this->Find($subscriptionID);

        if (!$toBeDeletedSub) {
            return null;
        }

        $toBeDeletedSub->delete();

        return true;
    }

    public function FindAll($search, $page, $limit): LengthAwarePaginator|null
    {
        //handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('subscriptions');

        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return Subscription::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }
}
