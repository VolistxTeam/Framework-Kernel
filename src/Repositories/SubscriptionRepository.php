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
            'plan_activated_at' => $inputs['plan_activated_at'],
            'plan_expires_at'   => $inputs['plan_expires_at'],
        ]);
    }

    public function Update($subscriptionID, array $inputs)
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        $plan_activated_at = $inputs['plan_activated_at'] ?? null;
        $plan_expires_at = $inputs['plan_expires_at'] ?? null;
        $plan_id = $inputs['plan_id'] ?? null;

        if (!$plan_expires_at && !$plan_id && !$plan_activated_at) {
            return $subscription;
        }

        if ($plan_id) {
            $subscription->plan_id = $plan_id;
        }
        if ($plan_activated_at) {
            $subscription->plan_activated_at = $plan_activated_at;
        }

        $subscription->plan_expires_at = $plan_expires_at;

        $subscription->save();

        return $subscription;
    }

    public function Find($subscriptionID): object|null
    {
        return Subscription::query()->where('id', $subscriptionID)->first();
    }

    public function Delete($subscriptionID): array|null
    {
        $toBeDeletedSub = $this->Find($subscriptionID);

        if (!$toBeDeletedSub) {
            return null;
        }

        $toBeDeletedSub->delete();

        return [
            'result' => 'true',
        ];
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
