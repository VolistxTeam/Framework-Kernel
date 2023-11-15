<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\DataTransferObjects\SubscriptionDTO;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class SubscriptionController extends Controller
{
    private SubscriptionRepository $subscriptionRepository;
    private IUserLoggingService $loggingService;

    public function __construct(SubscriptionRepository $subscriptionRepository, IUserLoggingService $loggingService)
    {
        $this->module = 'subscriptions';
        $this->subscriptionRepository = $subscriptionRepository;
        $this->loggingService = $loggingService;
    }

    /**
     * Create a new subscription.
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return JsonResponse
     */
    public function createSubscription(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateCreateValidation(array_merge($request->all(), [
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $status = Subscriptions::processUserActiveSubscriptionsStatus($userId) || Subscriptions::processUserInactiveSubscriptionsStatus($userId)
                ? SubscriptionStatus::INACTIVE : SubscriptionStatus::ACTIVE;

            $newSubscription = $this->subscriptionRepository->create([
                'user_id'      => $userId,
                'plan_id'      => $request->input('plan_id'),
                'activated_at' => $request->input('activated_at'),
                'expires_at'   => $request->input('expires_at'),
                'status'       => $status,
            ]);

            return response()->json(SubscriptionDTO::fromModel($newSubscription)->getDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Mutate a subscription by creating a new one and setting the previous subscription status to MUTATED.
     * USE WITH CAUTION, as it can put the system in an invalid state.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function mutateSubscription(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'mutate')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateUpdateValidation(array_merge($request->all(), [
                'subscription_id' => $subscriptionId,
                'user_id'         => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $mutatedSub = $this->subscriptionRepository->clone($userId, $subscriptionId, $request->all());

            if ($mutatedSub) {
                $this->subscriptionRepository->update($userId, $subscriptionId, [
                    'status' => SubscriptionStatus::DEACTIVATED,
                ]);
            }

            return response()->json(SubscriptionDTO::fromModel($mutatedSub)->getDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Delete a subscription.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function deleteSubscription(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateDeleteValidation(array_merge($request->all(), [
                'subscription_id' => $subscriptionId,
                'user_id'         => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->subscriptionRepository->delete($userId, $subscriptionId);

            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Cancel a subscription.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function cancelSubscription(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'cancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $cancelsAt = $request->input('cancels_at');

        $validator = $this->getModuleValidation($this->module)->generateCancelValidation(array_merge($request->all(), [
            'user_id'         => $userId,
            'subscription_id' => $subscriptionId,
            'cancels_at'      => $cancelsAt,
        ]));

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->find($userId, $subscriptionId);

        if ($subscription->status !== SubscriptionStatus::ACTIVE && $subscription->status !== SubscriptionStatus::INACTIVE) {
            return response()->json(Messages::E400(trans('volistx::subscriptions.can_not_cancel_subscription')), 400);
        }

        $updatedSub = $this->subscriptionRepository->update(
            $userId,
            $subscriptionId,
            [
                'cancels_at' => $cancelsAt,
            ]
        );

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->getDTO());
    }

    /**
     * Uncancel a subscription.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function uncancelSubscription(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'uncancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $validator = $this->getModuleValidation($this->module)->generateUncancelValidation([
            'user_id'         => $userId,
            'subscription_id' => $subscriptionId,
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->find($userId, $subscriptionId);

        if (($subscription->status !== SubscriptionStatus::ACTIVE && $subscription->status !== SubscriptionStatus::INACTIVE) || empty($subscription->cancels_at)) {
            return response()->json(Messages::E400(trans('volistx::subscription.can_not_uncancel')), 400);
        }

        $updatedSub = $this->subscriptionRepository->update(
            $userId,
            $subscriptionId,
            [
                'cancels_at' => null,
            ]
        );

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->getDTO());
    }

    /**
     * Get a subscription.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function getSubscription(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateGetValidation([
                'user_id'         => $userId,
                'subscription_id' => $subscriptionId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subscription = $this->subscriptionRepository->find($userId, $subscriptionId);

            if (!$subscription) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(SubscriptionDTO::fromModel($subscription)->getDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Get all subscriptions of a user.
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return JsonResponse
     */
    public function getSubscriptions(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = $this->getModuleValidation($this->module)->generateGetAllValidation([
                'user_id' => $userId,
                'page'    => $page,
                'limit'   => $limit,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subs = $this->subscriptionRepository->findAll($userId, $search, $page, $limit);

            if (!$subs) {
                return response()->json(Messages::E400(trans('invalid_search_column')), 400);
            }

            $items = [];
            foreach ($subs->items() as $item) {
                $items[] = SubscriptionDTO::fromModel($item)->getDTO();
            }

            return response()->json([
                'pagination' => [
                    'per_page' => $subs->perPage(),
                    'current'  => $subs->currentPage(),
                    'total'    => $subs->lastPage(),
                ],
                'items' => $items,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Get the logs of a subscription.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function getSubscriptionLogs(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'logs')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = $this->getModuleValidation($this->module)->generateGetLogsValidation([
                'subscription_id' => $subscriptionId,
                'user_id'         => $userId,
                'page'            => $page,
                'limit'           => $limit,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->loggingService->getSubscriptionLogs($userId, $subscriptionId, $search, $page, $limit);

            if ($logs === null) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
            }

            return response()->json($logs);
        } catch (Exception $exception) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Get the usages of a subscription.
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $subscriptionId
     *
     * @return JsonResponse
     */
    public function getSubscriptionUsages(Request $request, string $userId, string $subscriptionId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'stats')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateGetUsageValidation([
                'user_id'         => $userId,
                'subscription_id' => $subscriptionId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $usages = $this->loggingService->getSubscriptionUsages($userId, $subscriptionId);

            if (!$usages) {
                return response()->json(Messages::E500(), 500);
            }

            return response()->json($usages);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
