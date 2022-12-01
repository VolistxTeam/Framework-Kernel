<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Volistx\FrameworkKernel\DataTransferObjects\SubscriptionDTO;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Facades\AccessTokens;
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

            $validator = Validator::make(
                $request->all(),
                [
                    'user_id'      => ['bail', 'required', 'integer'],
                    'plan_id'      => ['bail', 'required', 'uuid', 'exists:plans,id'],
                    'activated_at' => ['bail', 'date'],
                    'expires_at'   => ['bail', 'present', 'date', 'nullable'],
                ],
                [
                    'user_id.required'      => trans('volistx::user_id.required'),
                    'user_id.integer'       => trans('volistx::user_id.integer'),
                    'plan_id.required'      => trans('volistx::plan_id.required'),
                    'plan_id.uuid'          => trans('volistx::plan_id.uuid'),
                    'plan_id.exists'        => trans('volistx::plan_id.exists'),
                    'activated_at.date'     => trans('volistx::activated_at.date'),
                    'expires_at.date'       => trans('volistx::expires_at.date'),
                ]
            );

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newSubscription = $this->subscriptionRepository->Create([
                'user_id'      => $request->input('user_id'),
                'plan_id'      => $request->input('plan_id'),
                'activated_at' => $request->input('activated_at'),
                'expires_at'   => $request->input('expires_at'),
                'status'       => SubscriptionStatus::INACTIVE,
            ]);

            return response()->json(SubscriptionDTO::fromModel($newSubscription)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    //This will create a new subscription while overriding the requested inputs. and then
    //Set the previous subscription status to MUTATED aka no longer in use
    //USE WITH CAUTION, IT CAN PUT THE SYSTEM IN INVALID STATE.
    public function MutateSubscription(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'mutate')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'plan_id'         => ['bail', 'sometimes', 'uuid', 'exists:plans,id'],
                'status'          => ['bail', 'sometimes', new Enum(SubscriptionStatus::class)],
                'activated_at'    => ['bail', 'sometimes', 'date'],
                'expires_at'      => ['bail', 'present', 'date', 'nullable'],
                'cancels_at'      => ['bail', 'sometimes', 'date'],
                'cancelled_at'    => ['bail', 'sometimes', 'date'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
                'hmac_token.max'           => trans('volistx::hmac_token.max'),
                'user_id.integer'          => trans('volistx::user_id.integer'),
                'plan_id.uuid'             => trans('volistx::plan_id.uuid'),
                'activated_at.date'        => trans('volistx::activated_at.date'),
                'expires_at.date'          => trans('volistx::expires_at.date'),
                'cancels_at.date'          => trans('volistx::cancels_at.date'),
                'cancelled_at.date'        => trans('volistx::cancelled_at.date'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $mutated_sub = $this->subscriptionRepository->Clone($subscription_id, $request->all());

            //if new sub created successfuly, set the old one status to INACTIVE so its not used
            if ($mutated_sub) {
                $this->subscriptionRepository->Update($subscription_id, [
                    'status' => SubscriptionStatus::DEACTIVATED,
                ]);
            }

            return response()->json(SubscriptionDTO::fromModel($mutated_sub)->GetDTO());
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
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
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

    public function CancelSubscription(Request $request, $subscription_id): JsonResponse
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'cancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $cancels_at = $request->input('cancels_at');

        $validator = Validator::make([
            'subscription_id' => $subscription_id,
            'cancels_at'      => $cancels_at,
        ], [
            'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            'cancels_at'      => ['bail', 'sometimes', 'date'],
        ], [
            'subscription_id.required' => trans('volistx::subscription_id.required'),
            'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
            'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
            'cancels_at.date'          => trans('volistx::cancels_at.date'),
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->Find($subscription_id);

        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
            return response()->json(Messages::E400("Can't cancel a subscription"), 400);
        }

        $updatedSub = $this->subscriptionRepository->Update(
            $subscription_id,
            [
                'cancels_at' => $cancels_at,
            ]
        );

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
    }

    public function UncancelSubscription(Request $request, $subscription_id): JsonResponse
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'uncancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $validator = Validator::make([
            'subscription_id' => $subscription_id,
        ], [
            'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
        ], [
            'subscription_id.required' => trans('volistx::subscription_id.required'),
            'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
            'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
            'cancels_at.date'          => trans('volistx::cancels_at.date'),
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->Find($subscription_id);

        if ($subscription->status !== SubscriptionStatus::ACTIVE || empty($subscription->cancels_at)) {
            return response()->json(Messages::E400(trans('volistx::subscription.can_not_uncancel')), 400);
        }

        $updatedSub = $this->subscriptionRepository->Update(
            $subscription_id,
            [
                'cancels_at' => null,
            ]
        );

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
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
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
                'page.integer'  => trans('volistx::page.integer'),
                'limit.integer' => trans('volistx::limit.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subs = $this->subscriptionRepository->FindAll($search, $page, $limit);

            if (!$subs) {
                return response()->json(Messages::E400(trans('invalid_search_column')), 400);
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
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
                'page.integer'             => trans('volistx::page.integer'),
                'limit.integer'            => trans('volistx::limit.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->loggingService->GetSubscriptionLogs($subscription_id, $search, $page, $limit);

            if ($logs === null) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
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

            $validator = Validator::make([
                'subscription_id' => $subscription_id,
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid'     => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists'   => trans('volistx::subscription_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $usages = $this->loggingService->GetSubscriptionUsages($subscription_id);
            if (!$usages) {
                return response()->json(Messages::E500(), 500);
            }

            return response()->json($usages);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
