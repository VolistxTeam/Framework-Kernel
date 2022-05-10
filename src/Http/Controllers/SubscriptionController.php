<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Volistx\FrameworkKernel\DataTransferObjects\SubscriptionDTO;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Keys;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
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

    public function CreateSubscription(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['bail', 'required', 'integer'],
                'plan_id' => ['bail', 'required', 'uuid', 'exists:plans,id'],
                'plan_activated_at' => ['bail', 'required', 'date'],
                'plan_expires_at' => ['bail', 'required', 'date', 'after:plan_activated_at'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newSubscription = $this->subscriptionRepository->Create([
                'user_id' => $request->input('user_id'),
                'plan_id' => $request->input('plan_id'),
                'hmac_token' => Keys::randomKey(32),
                'plan_activated_at' => $request->input('plan_activated_at'),
                'plan_expires_at' => $request->input('plan_expires_at'),
            ]);

            return response()->json(SubscriptionDTO::fromModel($newSubscription)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function UpdateSubscription(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'plan_activated_at' => ['bail', 'sometimes', 'date'],
                'hmac_token' => ['bail', 'sometimes', 'max:255'],
                'plan_expires_at' => ['bail',
                    Rule::requiredIf(function () use ($request) {
                        return !empty($request->input('plan_activated_at'));
                    })
                    , 'date', 'after:plan_activated_at'],
                'plan_id' => ['bail', 'sometimes', 'uuid', 'exists:plans,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedSub = $this->subscriptionRepository->Update($subscription_id, $request->all());

            if (!$updatedSub) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function DeleteSubscription(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'subscription_id' => $subscription_id,
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->subscriptionRepository->Delete($subscription_id);
            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscription(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'subscription_id' => $subscription_id,
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subscription = $this->subscriptionRepository->Find($subscription_id);

            if (!$subscription) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(SubscriptionDTO::fromModel($subscription)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptions(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = Validator::make([
                'page' => $page,
                'limit' => $limit,
            ], [
                '$page' => ['bail', 'sometimes', 'numeric'],
                'limit' => ['bail', 'sometimes', 'numeric'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subs = $this->subscriptionRepository->FindAll($search, $page, $limit);
            if (!$subs) {
                return response()->json(Messages::E500(), 500);
            }

            $items = [];
            foreach ($subs->items() as $item) {
                $items[] = SubscriptionDTO::fromModel($item)->GetDTO();
            }

            return response()->json([
                'pagination' => [
                    'per_page' => $subs->perPage(),
                    'current' => $subs->currentPage(),
                    'total' => $subs->lastPage(),
                ],
                'items' => $items,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptionLogs(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'logs')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = Validator::make(array_merge([
                'subscription_id' => $subscription_id,
                'page' => $page,
                'limit' => $limit,
            ]), [
                'subscription_id' => ['bail', 'required', 'exists:subscriptions,id'],
                '$page' => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->loggingService->GetSubscriptionLogs($subscription_id, $search, $page, $limit);
            if (!$logs) {
                return response()->json(Messages::E500(), 500);
            }

            return response()->json($logs);
        } catch (Exception $exception) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptionUsages(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'stats')) {
                return response()->json(Messages::E401(), 401);
            }

            $date = $request->input('date', Carbon::now()->format('Y-m'));
            $mode = $request->input('mode', 'detailed');

            $validator = Validator::make([
                'subscription_id' => $subscription_id,
                'date' => $date,
                'mode' => strtolower($mode),
            ], [
                'subscription_id' => ['bail', 'required', 'exists:subscriptions,id'],
                'date' => ['bail', 'sometimes', 'date'],
                'mode' => ['bail', 'sometimes', Rule::in(['detailed', 'focused'])],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $usages = $this->loggingService->GetSubscriptionUsages($subscription_id, $date, $mode);
            if (!$usages) {
                return response()->json(Messages::E500(), 500);
            }

            return response()->json($usages);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
