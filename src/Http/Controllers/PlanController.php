<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

            $validator = Validator::make($request->all(), [
                'name'        => ['bail', 'required', 'string', 'unique:plans'],
                'description' => ['bail', 'required', 'string'],
                'data'        => ['bail', 'required', 'array'],
                'price'       => ['bail', 'required', 'numeric'],
            ], [
                'name.required'         => 'The name is required.',
                'name.string'           => 'The name must be a string.',
                'name.unique'           => 'The name must be unique.',
                'description.required'  => 'The description is required.',
                'description.string'    => 'The description must be a string.',
                'data.required'         => 'The data is required.',
                'data.array'            => 'The data must be an array.',
                'price.required'        => 'The price is required.',
                'price.numeric'         => 'the price must be a numeric value',
            ]);

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

            $validator = Validator::make(array_merge($request->all(), [
                'plan_id' => $plan_id,
            ]), [
                'plan_id'     => ['bail', 'required', 'uuid', 'exists:plans,id'],
                'name'        => ['bail', 'sometimes', 'string', 'unique:plans'],
                'description' => ['bail', 'sometimes', 'string'],
                'data'        => ['bail', 'sometimes', 'array'],
                'price'       => ['bail', 'sometimes', 'numeric'],
            ], [
                'plan_id.required'      => 'The plan ID is required.',
                'plan_id.uuid'          => 'The plan ID must be a valid uuid.',
                'plan_id.exists'        => 'The plan with the given ID was not found.',
                'name.string'           => 'The name must be a string.',
                'name.unique'           => 'The name must be unique.',
                'description.string'    => 'The description must be a string.',
                'data.array'            => 'The data must be an array.',
                'price.numeric'         => 'the price must be a numeric value',
            ]);

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

            $validator = Validator::make([
                'plan_id' => $plan_id,
            ], [
                'plan_id' => ['bail', 'required', 'uuid', 'exists:plans,id'],
            ], [
                'plan_id.required' => 'The plan ID is required.',
                'plan_id.uuid'     => 'The plan ID must be a valid uuid.',
                'plan_id.exists'   => 'The plan with the given ID was not found.',
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

            $validator = Validator::make([
                'plan_id' => $plan_id,
            ], [
                'plan_id' => ['bail', 'required', 'uuid', 'exists:plans,id'],
            ], [
                'plan_id.required' => 'The plan ID is required.',
                'plan_id.uuid'     => 'The plan ID must be a valid uuid.',
                'plan_id.exists'   => 'The plan with the given ID was not found.',
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

            $plans = $this->planRepository->FindAll($search, (int) $page, (int) $limit);

            if (!$plans) {
                return response()->json(Messages::E400('Invalid search column'), 400);
            }

            $items = [];
            foreach ($plans->items() as $item) {
                $items[] = PlanDTO::fromModel($item)->GetDTO();
            }

            return response()->json([
                'pagination' => [
                    'per_page' => $plans->perPage(),
                    'current'  => $plans->currentPage(),
                    'total'    => $plans->lastPage(),
                ],
                'items' => $items,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
