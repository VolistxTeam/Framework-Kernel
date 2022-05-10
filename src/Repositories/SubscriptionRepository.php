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
            'user_id'           => $inputs['user_id'],
            'plan_id'           => $inputs['plan_id'],
            'hmac_token'        => $inputs['hmac_token'],
            'plan_activated_at' => $inputs['plan_activated_at'],
            'plan_expires_at'   => $inputs['plan_expires_at'],
        ]);
    }

    public function Update($subscriptionID, array $inputs): ?object
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        $plan_activated_at = $inputs['plan_activated_at'] ?? null;
        $plan_expires_at = $inputs['plan_expires_at'] ?? null;
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

        if ($plan_expires_at !== null) {
            $subscription->plan_expires_at = $plan_expires_at;
        }

        $subscription->save();

        return $subscription;
    }

    public function Find($subscriptionID): ?object
    {
        return Subscription::with('plan')->where('id', $subscriptionID)->first();
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

    public function FindAll($needle, $page, $limit): LengthAwarePaginator
    {
        $columns = Schema::getColumnListing('subscriptions');

        return Subscription::query()->where(function ($query) use ($needle, $columns) {
            foreach ($columns as $column) {
                $query->orWhere("subscriptions.$column", 'LIKE', "%$needle%");
            }
        })->paginate($limit, ['*'], 'page', $page);
    }
}
