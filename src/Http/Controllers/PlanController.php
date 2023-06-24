<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\DataTransferObjects\PlanDTO;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\PlanRepository;

class PlanController extends Controller
{

    private PlanRepository $planRepository;

    public function __construct(PlanRepository $planRepository)
    {
        $this->module = 'plans';
        $this->planRepository = $planRepository;
    }

    public function CreatePlan(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateCreateValidation($request->all());

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newPlan = $this->planRepository->Create($request->all());

            return response()->json(PlanDTO::fromModel($newPlan)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function UpdatePlan(Request $request, $plan_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateUpdateValidation(array_merge($request->all(), [
                'plan_id' => $plan_id,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedPlan = $this->planRepository->Update($plan_id, $request->all());

            if (!$updatedPlan) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PlanDTO::fromModel($updatedPlan)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function DeletePlan(Request $request, $plan_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateDeleteValidation([
                'plan_id' => $plan_id,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->planRepository->Delete($plan_id);
            if ($result === null) {
                return response()->json(Messages::E404(), 404);
            }
            if ($result === false) {
                return response()->json(Messages::E409(), 409);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPlan(Request $request, $plan_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateGetValidation([
                'plan_id' => $plan_id,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $plan = $this->planRepository->Find($plan_id);

            if (!$plan) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PlanDTO::fromModel($plan)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPlans(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = $this->GetModuleValidation($this->module)->generateGetAllValidation([
                'page' => $page,
                'limit' => $limit,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $plans = $this->planRepository->FindAll($search, (int)$page, (int)$limit);

            if (!$plans) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
            }

            $items = [];
            foreach ($plans->items() as $item) {
                $items[] = PlanDTO::fromModel($item)->GetDTO();
            }

            return response()->json([
                'pagination' => [
                    'per_page' => $plans->perPage(),
                    'current' => $plans->currentPage(),
                    'total' => $plans->lastPage(),
                ],
                'items' => $items,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
