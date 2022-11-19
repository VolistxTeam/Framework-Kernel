<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Models\Subscription;

class SubscriptionRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return Subscription::query()->create([
            'user_id'      => $inputs['user_id'],
            'plan_id'      => $inputs['plan_id'],
            'status'       => SubscriptionStatus::ACTIVE,
            'activated_at' => Carbon::now(),
            'expires_at'   => $inputs['expires_at'],
            'cancels_at'   => null,
            'cancelled_at' => null,
        ]);
    }

    public function Clone($subscriptionID, $inputs): Builder|Model|null
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        return Subscription::query()->create([
            'user_id'           => $subscription->user_id,
            'plan_id'           => $inputs['plan_id'] ?? $subscription->plan_id,
            'status'            => $inputs['status'] ?? $subscription->status,
            'activated_at'      => $inputs['activated_at'] ?? Carbon::now(),
            'expires_at'        => $inputs['expires_at'] ?? $subscription->expires_at,
            'cancels_at'        => $inputs['cancels_at'] ?? $subscription->cancels_at,
            'cancelled_at'      => $inputs['cancelled_at'] ?? $subscription->cancelled_at,
        ]);
    }

    public function Update($subscriptionID, array $inputs): ?object
    {
        $subscription = $this->Find($subscriptionID);

        if (!$subscription) {
            return null;
        }

        if (array_key_exists('status',$inputs)) {
            $subscription->status = $inputs['status'];
        }

        if (array_key_exists('cancels_at',$inputs)) {
            $subscription->cancels_at = $inputs['cancels_at'];
        }

        if (array_key_exists('cancelled_at', $inputs)) {
            $subscription->cancelled_at = $inputs['cancelled_at'];
        }

        $subscription->save();

        return $subscription;
    }

    public function Find($subscriptionID): ?object
    {
        return Subscription::with('plan')->where('id', $subscriptionID)->first();
    }

    public function FindUserActiveSubscription($user_id): Builder|Model|null
    {
        return Subscription::with('plan')
            ->where('user_id', $user_id)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();
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
