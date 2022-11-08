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
                'user_id'           => ['bail', 'required', 'integer'],
                'plan_id'           => ['bail', 'required', 'uuid', 'exists:plans,id',
                    Rule::exists('plans', 'id')->where(function ($query) {
                        return $query->where('is_active', true);
                    }), ],
                'plan_activated_at' => ['bail', 'required', 'date'],
                'plan_expires_at'   => ['bail', 'sometimes', 'date', 'nullable', 'after:plan_activated_at'],
            ], [
                'user_id.required'           => 'The user ID is required.',
                'user_id.integer'            => 'The user ID must be an integer.',
                'plan_id.required'           => 'The plan ID is required.',
                'plan_id.uuid'               => 'The plan ID must be a UUID.',
                'plan_id.exists'             => 'The plan with the given ID was not found.',
                'plan_activated_at.required' => 'The plan activated at is required.',
                'plan_activated_at.date'     => 'The plan activated at must be a valid date.',
                'plan_expires_at.required'   => 'The plan expires at is required.',
                'plan_expires_at.date'       => 'The plan expires at must be a valid date.',
                'plan_expires_at.after'      => 'The plan expires at must be after the plan activated at.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newSubscription = $this->subscriptionRepository->Create([
                'user_id'           => $request->input('user_id'),
                'plan_id'           => $request->input('plan_id'),
                'hmac_token'        => Keys::randomKey(32),
                'plan_activated_at' => $request->input('plan_activated_at'),
                'plan_expires_at'   => $request->input('plan_expires_at'),
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
                'subscription_id'   => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'plan_activated_at' => ['bail', 'sometimes', 'date'],
                'hmac_token'        => ['bail', 'sometimes', 'max:255'],
                'plan_expires_at'   => ['bail',
                    Rule::requiredIf(function () use ($request) {
                        return !empty($request->input('plan_activated_at'));
                    }), 'date', 'after:plan_activated_at', ],
                'plan_id' => ['bail', 'sometimes', 'uuid', 'exists:plans,id'],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
                'plan_activated_at.date'   => 'The plan activated at date must be a valid date.',
                'plan_expires_at.date'     => 'The plan expires at date must be a valid date.',
                'plan_expires_at.after'    => 'The plan expires at date must be after the plan activated at date.',
                'plan_id.uuid'             => 'The plan ID must be a valid UUID.',
                'plan_id.exists'           => 'The plan with the given ID was not found.',
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
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
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

    public function CancelSubscription(Request $request, $subscription_id)
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'cancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $immediately = $request->input('immediately', false);
        $ignoreFallback = $request->input('ignore_fallback', false);

        $validator = Validator::make([
            'subscription_id' => $subscription_id,
            'immediately'     => $immediately,
            'ignore_fallback' => $ignoreFallback,
        ], [
            'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            'immediately'     => ['bail', 'sometimes', 'boolean'],
            'ignore_fallback' => ['bail', 'sometimes', 'boolean'],
        ], [
            'subscription_id.required' => 'The subscription ID is required.',
            'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
            'subscription_id.exists'   => 'The subscription with the given ID was not found.',
            'immediately.boolean'      => 'The immediately flag must be a boolean value.',
            'ignore_fallback.boolean'  => 'The ignore fallback flag must be a boolean value.',
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        if (!$ignoreFallback && config('volistx.fallback_plan.id') !== null) {
            $this->subscriptionRepository->Update($subscription_id, [
                'plan_id' => config('volistx.fallback_plan.id'),
            ]);

            $updatedSub = $this->subscriptionRepository->Find($subscription_id);

            if (!$updatedSub) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
        }

        $cancels_at = Carbon::now();

        if ($immediately) {
            $this->subscriptionRepository->Update($subscription_id, [
                'plan_expires_at'   => $cancels_at,
                'plan_cancels_at'   => $cancels_at,
                'plan_cancelled_at' => $cancels_at,
            ]);
        } else {
            $this->subscriptionRepository->Update($subscription_id, [
                'plan_cancels_at'   => $cancels_at,
                'plan_cancelled_at' => $cancels_at,
            ]);
        }

        $updatedSub = $this->subscriptionRepository->Find($subscription_id);
        if (!$updatedSub) {
            return response()->json(Messages::E404(), 404);
        }

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
    }

    public function UncancelSubscription(Request $request, $subscription_id)
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'cancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $validator = Validator::make([
            'subscription_id' => $subscription_id,
        ], [
            'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
        ], [
            'subscription_id.required' => 'The subscription ID is required.',
            'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
            'subscription_id.exists'   => 'The subscription with the given ID was not found.',
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $this->subscriptionRepository->Update($subscription_id, [
            'plan_cancels_at'   => null,
            'plan_cancelled_at' => null,
        ]);

        $updatedSub = $this->subscriptionRepository->Find($subscription_id);

        if (!$updatedSub) {
            return response()->json(Messages::E404(), 404);
        }

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
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
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
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
                'page'  => $page,
                'limit' => $limit,
            ], [
                'page'  => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ], [
                'page.integer'              => 'The page must be an integer.',
                'limit.integer'             => 'The limit must be an integer.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subs = $this->subscriptionRepository->FindAll($search, $page, $limit);

            if (!$subs) {
                return response()->json(Messages::E400('Invalid search column'), 400);
            }

            $items = [];
            foreach ($subs->items() as $item) {
                $items[] = SubscriptionDTO::fromModel($item)->GetDTO();
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
                'page'            => $page,
                'limit'           => $limit,
            ]), [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'page'            => ['bail', 'sometimes', 'integer'],
                'limit'           => ['bail', 'sometimes', 'integer'],
            ], [
                'subscription_id.required'  => 'The subscription ID is required.',
                'subscription_id.uuid'      => 'The subscription ID must be a valid UUID.',
                'subscription_id.exists'    => 'The subscription with the given ID was not found.',
                'page.integer'              => 'The page must be an integer.',
                'limit.integer'             => 'The limit must be an integer.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->loggingService->GetSubscriptionLogs($subscription_id, $search, $page, $limit);

            if (!$logs) {
                return response()->json(Messages::E400('Invalid search column'), 400);
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
                'date'            => $date,
                'mode'            => strtolower($mode),
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'date'            => ['bail', 'sometimes', 'date'],
                'mode'            => ['bail', 'sometimes', Rule::in(['detailed', 'focused'])],
            ], [
                'subscription_id.required'  => 'The subscription ID is required.',
                'subscription_id.uuid'      => 'The subscription ID must be a valid UUID.',
                'subscription_id.exists'    => 'The subscription with the given ID was not found.',
                'date.date'                 => 'The date must be a valid date.',
                'mode.in'                   => 'The mode must be either "detailed" or "focused"',
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
