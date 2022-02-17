<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Volistx\FrameworkKernel\DataTransferObjects\SubscriptionDTO;
use Volistx\FrameworkKernel\DataTransferObjects\UserLogDTO;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\Interfaces\IUserLogRepository;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

class SubscriptionController extends Controller
{
    private SubscriptionRepository $subscriptionRepository;
    private IUserLogRepository $logRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository, IUserLogRepository $logRepository)
    {
        $this->module = 'subscriptions';
        $this->subscriptionRepository = $subscriptionRepository;
        $this->logRepository = $logRepository;
    }

    public function CreateSubscription(Request $request): JsonResponse
    {
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'create')) {
            return response()->json(Messages::E401(), 401);
        }

        $validator = Validator::make($request->all(), [
            'user_id'           => ['bail', 'required', 'integer'],
            'plan_id'           => ['bail', 'required', 'uuid', 'exists:plans,id'],
            'plan_activated_at' => ['bail', 'required', 'date'],
            'plan_expires_at'   => ['bail', 'required', 'date', 'after:plan_activated_at'],
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        try {
            $newSubscription = $this->subscriptionRepository->Create($request->all());
            if (!$newSubscription) {
                return response()->json(Messages::E500(), 500);
            }

            return response()->json(SubscriptionDTO::fromModel($newSubscription)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function UpdateSubscription(Request $request, $subscription_id): JsonResponse
    {
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'update')) {
            return response()->json(Messages::E401(), 401);
        }

        $validator = Validator::make(array_merge($request->all(), [
            'subscription_id' => $subscription_id,
        ]), [
            'subscription_id'   => ['bail', 'required', 'uuid', 'exists:subscriptions,id'],
            'plan_activated_at' => ['bail', 'sometimes', 'string'],
            'plan_expires_at'   => ['bail', 'sometimes', 'string'],
            'plan_id'           => ['bail', 'sometimes', 'exists:plans,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        try {
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
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'delete')) {
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

        try {
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
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'view')) {
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

        try {
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
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'view-all')) {
            return response()->json(Messages::E401(), 401);
        }

        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 50);

        $validator = Validator::make([
            'page'  => $page,
            'limit' => $limit,
        ], [
            '$page' => ['bail', 'sometimes', 'numeric'],
            'limit' => ['bail', 'sometimes', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        try {
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
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'logs')) {
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
            'subscription_id' => ['bail', 'required', 'exists:subscriptions,id'],
            '$page'           => ['bail', 'sometimes', 'integer'],
            'limit'           => ['bail', 'sometimes', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        try {
            $logs = $this->logRepository->FindSubscriptionLogs($subscription_id, $search, $page, $limit);
            if (!$logs) {
                return response()->json(Messages::E500(), 500);
            }

            $logDTOs = [];
            foreach ($logs['data'] as $log) {
                $logDTOs[] = UserLogDTO::fromModel($log)->GetDTO();
            }

            return response()->json([
                'pagination' => [
                    'per_page' => $logs['per_page'],
                    'current'  => $logs['current_page'],
                    'total'    => $logs['last_page'],
                ],
                'items' => $logDTOs,
            ]);
        } catch (Exception $exception) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetSubscriptionStats(Request $request, $subscription_id): JsonResponse
    {
        if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'stats')) {
            return response()->json(Messages::E401(), 401);
        }

        $date = $request->input('date', Carbon::now()->format('Y-m'));

        $validator = Validator::make([
            'subscription_id' => $subscription_id,
            'date'            => $date,
        ], [
            'subscription_id' => ['bail', 'required', 'exists:subscriptions,id'],
            'date'            => ['bail', 'sometimes', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json(Messages::E400($validator->errors()->first()), 400);
        }

        try {
            $groupedLogs = $this->logRepository->FindSubscriptionLogsInMonth($subscription_id, $date);

            $specifiedDate = Carbon::parse($date);
            $thisDate = Carbon::now();
            $lastDay = $specifiedDate->format('Y-m') == $thisDate->format('Y-m') ? $thisDate->day : (int) $specifiedDate->format('t');

            $totalCount = 0;

            $stats = [];
            for ($i = 1; $i <= $lastDay; $i++) {
                $groupedCount = isset($groupedLogs[$i]) ? count($groupedLogs[$i]) : 0;
                $totalCount += $groupedCount;
                $stats[] = [
                    'date'  => $specifiedDate->format('Y-m-').sprintf('%02d', $i),
                    'count' => $groupedCount,
                ];
            }

            $requestsCount = $this->subscriptionRepository->Find($subscription_id)->plan()->first()->data['requests'];

            return response()->json([
                'usage' => [
                    'current' => $totalCount,
                    'max'     => $requestsCount,
                    'percent' => $requestsCount ? (float) number_format(($totalCount * 100) / $requestsCount, 2) : null,
                ],
                'details' => $stats,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
