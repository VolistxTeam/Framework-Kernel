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
                    'user_id'    => ['bail', 'required', 'integer'],
                    'plan_id'    => ['bail', 'required', 'uuid', 'exists:plans,id'],
                    'expires_at' => ['bail', 'required', 'date', 'nullable'],
                ],
                [
                    'user_id.required' => 'The user ID is required.',
                    'user_id.integer'  => 'The user ID must be an integer.',
                    'plan_id.required' => 'The plan ID is required.',
                    'plan_id.uuid'     => 'The plan ID must be a UUID.',
                    'plan_id.exists'   => 'The plan with the given ID was not found.',
                    'expires_at.date'  => 'Expiration at must be a valid date.',
                ]
            );

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $existingSubscription = $this->subscriptionRepository->FindUserActiveSubscription($request->input('user_id'));

            if ($existingSubscription) {
                return response()->json(Messages::E400('User have an active subscription'), 400);
            }

            $newSubscription = $this->subscriptionRepository->Create([
                'user_id'    => $request->input('user_id'),
                'plan_id'    => $request->input('plan_id'),
                'expires_at' => $request->input('expires_at'),
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
                'expires_at'      => ['bail', 'sometimes', 'date'],
                'cancels_at'      => ['bail', 'sometimes', 'date'],
                'cancelled_at'    => ['bail', 'sometimes', 'date'],
            ], [
                'subscription_id.required' => 'Subscription ID is required.',
                'subscription_id.uuid'     => 'Subscription ID must be a valid UUID.',
                'hmac_token.max'           => 'Hmac_Token must not exceed 255 chars',
                'subscription_id.exists'   => 'Subscription with the given ID was not found.',
                'user_id.integer'          => 'User ID must be a number',
                'plan_id.uuid'             => 'Plan ID must be a uuid',
                'activated_at.date'        => 'Activation should be a valid date',
                'expires_at.date'          => 'Expiration should be a valid date',
                'cancels_at.date'          => 'Cancellation should be a valid date',
                'cancelled_at.date'        => 'Cancellation should be a valid date',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $mutated_sub = $this->subscriptionRepository->Clone($subscription_id, $request->all());

            //if new sub created successfuly, set the old one status to INACTIVE so its not used
            if ($mutated_sub) {
                $this->subscriptionRepository->Update($subscription_id, [
                    'status' => SubscriptionStatus::INACTIVE,
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
            'subscription_id.required' => 'The subscription ID is required.',
            'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
            'subscription_id.exists'   => 'The subscription with the given ID was not found.',
            'cancels_at.date'          => 'Cancellation must be a valid date',
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
        if (!Permissions::check(AccessTokens::getToken(), $this->module, 'un-cancel')) {
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
            'cancels_at.date'          => 'Cancellation must be a valid date.',
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        $subscription = $this->subscriptionRepository->Find($subscription_id);

        if ($subscription->status !== SubscriptionStatus::ACTIVE || empty($subscription->cancels_at)) {
            return response()->json(Messages::E400("Can't uncancel a subscription"), 400);
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
                'page.integer'  => 'The page must be an integer.',
                'limit.integer' => 'The limit must be an integer.',
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
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid UUID.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
                'page.integer'             => 'The page must be an integer.',
                'limit.integer'            => 'The limit must be an integer.',
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
