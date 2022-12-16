<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Volistx\FrameworkKernel\DataTransferObjects\SubscriptionDTO;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;
use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Events\SubscriptionCreated;
use Volistx\FrameworkKernel\Events\SubscriptionUpdated;
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

    public function CreateSubscription(Request $request, $user_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(
                array_merge($request->all(), [
                    'user_id' => $user_id,
                ]),
                [
                    'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
                    'plan_id' => ['bail', 'required', 'uuid', 'exists:plans,id'],
                    'activated_at' => ['bail', 'required', 'date'],
                    'expires_at' => ['bail', 'present', 'date', 'nullable'],
                ],
                [
                    'user_id.required' => trans('volistx::user_id.required'),
                    'user_id.integer' => trans('volistx::user_id.integer'),
                    'user_id.exists' => trans('volistx::user_id.exists'),
                    'plan_id.required' => trans('volistx::plan_id.required'),
                    'plan_id.uuid' => trans('volistx::plan_id.uuid'),
                    'plan_id.exists' => trans('volistx::plan_id.exists'),
                    'activated_at.date' => trans('volistx::activated_at.date'),
                    'activated_at.required' => trans('volistx::activated_at.required'),
                    'expires_at.date' => trans('volistx::expires_at.date'),
                    'expires_at.present' => trans('volistx::expires_at.present'),
                ]
            );

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newSubscription = $this->subscriptionRepository->Create([
                'user_id' => $user_id,
                'plan_id' => $request->input('plan_id'),
                'activated_at' => $request->input('activated_at'),
                'expires_at' => $request->input('expires_at'),
                'status' => SubscriptionStatus::INACTIVE,
            ]);

            Event::dispatch(new SubscriptionCreated($newSubscription->id));

            return response()->json(SubscriptionDTO::fromModel($newSubscription)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    //This will create a new subscription while overriding the requested inputs. and then
    //Set the previous subscription status to MUTATED aka no longer in use
    //USE WITH CAUTION, IT CAN PUT THE SYSTEM IN INVALID STATE.
    public function MutateSubscription(Request $request, $user_id, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'mutate')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'user_id' => $user_id,
            ]), [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
                'plan_id' => ['bail', 'sometimes', 'uuid', 'exists:plans,id'],
                'status' => ['bail', 'sometimes', new Enum(SubscriptionStatus::class)],
                'activated_at' => ['bail', 'sometimes', 'date'],
                'expires_at' => ['bail', 'present', 'date', 'nullable'],
                'cancels_at' => ['bail', 'sometimes', 'date'],
                'cancelled_at' => ['bail', 'sometimes', 'date'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists' => trans('volistx::subscription_id.exists'),
                'hmac_token.max' => trans('volistx::hmac_token.max'),
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'user_id.exists' => trans('volistx::user_id.exists'),
                'plan_id.uuid' => trans('volistx::plan_id.uuid'),
                'activated_at.date' => trans('volistx::activated_at.date'),
                'expires_at.date' => trans('volistx::expires_at.date'),
                'cancels_at.date' => trans('volistx::cancels_at.date'),
                'cancelled_at.date' => trans('volistx::cancelled_at.date'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $mutated_sub = $this->subscriptionRepository->Clone($user_id, $subscription_id, $request->all());

            Event::dispatch(new SubscriptionCreated($mutated_sub->id));


            //if new sub created successfuly, set the old one status to INACTIVE so its not used
            if ($mutated_sub) {
                $this->subscriptionRepository->Update($user_id, $subscription_id, [
                    'status' => SubscriptionStatus::DEACTIVATED,
                ]);

                Event::dispatch(new SubscriptionUpdated($subscription_id));
            }


            return response()->json(SubscriptionDTO::fromModel($mutated_sub)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function DeleteSubscription(Request $request, $user_id, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'subscription_id' => $subscription_id,
                'user_id' => $user_id,
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists' => trans('volistx::subscription_id.exists'),
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'user_id.exists' => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->subscriptionRepository->Delete($user_id, $subscription_id);

            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function CancelSubscription(Request $request, $user_id, $subscription_id): JsonResponse
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'cancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $cancels_at = $request->input('cancels_at');

        $validator = Validator::make([
            'user_id' => $user_id,
            'subscription_id' => $subscription_id,
            'cancels_at' => $cancels_at,
        ], [
            'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
            'cancels_at' => ['bail', 'sometimes', 'date'],
        ], [
            'subscription_id.required' => trans('volistx::subscription_id.required'),
            'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
            'subscription_id.exists' => trans('volistx::subscription_id.exists'),
            'user_id.required' => trans('volistx::user_id.required'),
            'user_id.integer' => trans('volistx::user_id.integer'),
            'user_id.exists' => trans('volistx::user_id.exists'),
            'cancels_at.date' => trans('volistx::cancels_at.date'),
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->Find($user_id, $subscription_id);

        if ($subscription->status !== SubscriptionStatus::ACTIVE && $subscription->status !== SubscriptionStatus::INACTIVE) {
            return response()->json(Messages::E400(trans('volistx::subscriptions.can_not_cancel_subscription')), 400);
        }

        $updatedSub = $this->subscriptionRepository->Update(
            $user_id,
            $subscription_id,
            [
                'cancels_at' => $cancels_at,
            ]
        );

        Event::dispatch(new SubscriptionUpdated($subscription_id));

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
    }

    public function UncancelSubscription(Request $request, $user_id, $subscription_id): JsonResponse
    {
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'uncancel')) {
            return response()->json(Messages::E401(), 401);
        }

        $validator = Validator::make([
            'user_id' => $user_id,
            'subscription_id' => $subscription_id,
        ], [
            'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
        ], [
            'subscription_id.required' => trans('volistx::subscription_id.required'),
            'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
            'subscription_id.exists' => trans('volistx::subscription_id.exists'),
            'user_id.required' => trans('volistx::user_id.required'),
            'user_id.integer' => trans('volistx::user_id.integer'),
            'user_id.exists' => trans('volistx::user_id.exists'),
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->Find($user_id, $subscription_id);

        if (($subscription->status !== SubscriptionStatus::ACTIVE && $subscription->status !== SubscriptionStatus::INACTIVE) || empty($subscription->cancels_at)) {
            return response()->json(Messages::E400(trans('volistx::subscription.can_not_uncancel')), 400);
        }

        $updatedSub = $this->subscriptionRepository->Update(
            $user_id,
            $subscription_id,
            [
                'cancels_at' => null,
            ]
        );

        Event::dispatch(new SubscriptionUpdated($subscription_id));

        return response()->json(SubscriptionDTO::fromModel($updatedSub)->GetDTO());
    }

    public function GetSubscription(Request $request, $user_id, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'user_id' => $user_id,
                'subscription_id' => $subscription_id,
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists' => trans('volistx::subscription_id.exists'),
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'user_id.exists' => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subscription = $this->subscriptionRepository->Find($user_id, $subscription_id);

            if (!$subscription) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(SubscriptionDTO::fromModel($subscription)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptions(Request $request, $user_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = Validator::make([
                'user_id' => $user_id,
                'page' => $page,
                'limit' => $limit,
            ], [
                'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
                'page' => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ], [
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'user_id.exists' => trans('volistx::user_id.exists'),
                'page.integer' => trans('volistx::page.integer'),
                'limit.integer' => trans('volistx::limit.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $subs = $this->subscriptionRepository->FindAll($user_id, $search, $page, $limit);

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
                    'current' => $subs->currentPage(),
                    'total' => $subs->lastPage(),
                ],
                'items' => $items,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptionLogs(Request $request, $user_id, $subscription_id): JsonResponse
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
                'user_id' => $user_id,
                'page' => $page,
                'limit' => $limit,
            ]), [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
                'page' => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists' => trans('volistx::subscription_id.exists'),
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'user_id.exists' => trans('volistx::user_id.exists'),
                'page.integer' => trans('volistx::page.integer'),
                'limit.integer' => trans('volistx::limit.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->loggingService->GetSubscriptionLogs($user_id, $subscription_id, $search, $page, $limit);

            if ($logs === null) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
            }

            return response()->json($logs);
        } catch (Exception $exception) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptionUsages(Request $request, $user_id, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'stats')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'user_id' => $user_id,
                'subscription_id' => $subscription_id,
            ], [
                'subscription_id' => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
                'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
            ], [
                'subscription_id.required' => trans('volistx::subscription_id.required'),
                'subscription_id.uuid' => trans('volistx::subscription_id.uuid'),
                'subscription_id.exists' => trans('volistx::subscription_id.exists'),
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'user_id.exists' => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $usages = $this->loggingService->GetSubscriptionUsages($user_id, $subscription_id);

            if (!$usages) {
                return response()->json(Messages::E500(), 500);
            }

            return response()->json($usages);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
