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
    /**
     * Create a new subscription.
     *
     * @param array $inputs [user_id, plan_id, status, activated_at, expires_at]
     *
     * @return Model|Builder
     */
    public function Create(array $inputs): Model|Builder
    {
        return Subscription::query()->create([
            'user_id'      => $inputs['user_id'],
            'plan_id'      => $inputs['plan_id'],
            'status'       => $inputs['status'],
            'activated_at' => $inputs['activated_at'] ?? Carbon::now(),
            'expires_at'   => $inputs['expires_at'],
            'cancels_at'   => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Clone an existing subscription.
     *
     * @param string $userId
     * @param string $subscriptionId
     * @param array  $inputs         [plan_id, status, activated_at, expires_at, expired_at, cancels_at, cancelled_at]
     *
     * @return Builder|Model|null
     */
    public function Clone(string $userId, string $subscriptionId, array $inputs): Builder|Model|null
    {
        $subscription = $this->Find($userId, $subscriptionId);

        if (!$subscription) {
            return null;
        }

        return Subscription::query()->create([
            'user_id'      => $userId,
            'plan_id'      => $inputs['plan_id'] ?? $subscription->plan_id,
            'status'       => $inputs['status'] ?? $subscription->status,
            'activated_at' => $inputs['activated_at'] ?? $subscription->activated_at,
            'expires_at'   => $inputs['expires_at'] ?? $subscription->expires_at,
            'expired_at'   => $inputs['expired_at'] ?? $subscription->expired_at,
            'cancels_at'   => $inputs['cancels_at'] ?? $subscription->cancels_at,
            'cancelled_at' => $inputs['cancelled_at'] ?? $subscription->cancelled_at,
        ]);
    }

    /**
     * Update an existing subscription.
     *
     * @param string $userId
     * @param string $subscriptionId
     * @param array  $inputs         [status, cancels_at, cancelled_at, expires_at, expired_at]
     *
     * @return object|null
     */
    public function Update(string $userId, string $subscriptionId, array $inputs): ?object
    {
        $subscription = $this->Find($userId, $subscriptionId);

        if (!$subscription) {
            return null;
        }

        if (array_key_exists('status', $inputs)) {
            $subscription->status = $inputs['status'];
        }

        if (array_key_exists('cancels_at', $inputs)) {
            $subscription->cancels_at = $inputs['cancels_at'];
        }

        if (array_key_exists('cancelled_at', $inputs)) {
            $subscription->cancelled_at = $inputs['cancelled_at'];
        }

        if (array_key_exists('expires_at', $inputs)) {
            $subscription->expires_at = $inputs['expires_at'];
        }

        if (array_key_exists('expired_at', $inputs)) {
            $subscription->expired_at = $inputs['expired_at'];
        }

        $subscription->save();

        return $subscription;
    }

    /**
     * Find a subscription by user ID and subscription ID.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return object|null
     */
    public function Find(string $userId, string $subscriptionId): ?object
    {
        return Subscription::with('plan')
            ->where('id', $subscriptionId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Find the active subscription for a user.
     *
     * @param string $userId
     *
     * @return Builder|Model|null
     */
    public function FindUserActiveSubscription(string $userId): Builder|Model|null
    {
        return Subscription::with('plan')
            ->where('user_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();
    }

    /**
     * Find the inactive subscription with the earliest activation date for a user.
     *
     * @param string $userId
     *
     * @return Builder|Model|null
     */
    public function FindUserInactiveSubscription(string $userId): Builder|Model|null
    {
        return Subscription::with('plan')
            ->where('user_id', $userId)
            ->where('status', SubscriptionStatus::INACTIVE)
            ->orderBy('activated_at', 'ASC')
            ->first();
    }

    /**
     * Delete a subscription by user ID and subscription ID.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return bool|null
     */
    public function Delete(string $userId, string $subscriptionId): ?bool
    {
        $toBeDeletedSub = $this->Find($userId, $subscriptionId);

        if (!$toBeDeletedSub) {
            return null;
        }

        $toBeDeletedSub->delete();

        return true;
    }

    /**
     * Find all subscriptions for a user with pagination support.
     *
     * @param string $userId
     * @param string $search
     * @param int    $page
     * @param int    $limit
     *
     * @return LengthAwarePaginator|null
     */
    public function FindAll(string $userId, string $search, int $page, int $limit): LengthAwarePaginator|null
    {
        // Handle empty search
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
            ->where('user_id', $userId)
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }
}
