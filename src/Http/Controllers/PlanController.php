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
                'name'        => ['bail', 'required', 'string'],
                'tag'         => ['bail', 'required', 'string', 'unique:plans,tag'],
                'description' => ['bail', 'required', 'string'],
                'data'        => ['bail', 'required', 'array'],
                'price'       => ['bail', 'required', 'numeric'],
                'tier'        => ['bail', 'required', 'integer', 'unique:plans,tier'],
                'custom'      => ['bail', 'required', 'boolean'],
            ], [
                'name.required'          => __('name.required'),
                'name.string'            => __('name.string'),
                'tag.required'           => __('tag.required'),
                'tag.string'             => __('tag.string'),
                'tag.unique'             => __('tag.unique'),
                'description.required'   => __('description.required'),
                'description.string'     => __('description.string'),
                'data.required'          => __('data.required'),
                'data.array'             => __('data.array'),
                'price.required'         => __('price.required'),
                'price.numeric'          => __('price.numeric'),
                'tier.required'          => __('tier.required'),
                'tier.integer'           => __('tier.integer'),
                'tier.unique'            => __('tier.unique'),
                'custom.required'        => __('custom.required'),
                'custom.boolean'         => __('custom.boolean'),
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
                'name'        => ['bail', 'sometimes', 'string'],
                'tag'         => ['bail', 'sometimes', 'string', 'unique:plans,tag'],
                'description' => ['bail', 'sometimes', 'string'],
                'data'        => ['bail', 'sometimes', 'array'],
                'price'       => ['bail', 'sometimes', 'numeric'],
                'tier'        => ['bail', 'sometimes', 'integer'],
                'custom'      => ['bail', 'sometimes', 'boolean'],
                'is_active'   => ['bail', 'sometimes', 'boolean'],
            ], [
                'plan_id.required'       => __('plan_id.required'),
                'plan_id.uuid'           => __('plan_id.uuid'),
                'plan_id.exists'         => __('plan_id.exists'),
                'name.string'            => __('name.string'),
                'tag.unique'             => __('tag.unique'),
                'description.string'     => __('description.string'),
                'data.array'             => __('data.array'),
                'price.numeric'          => __('price.numeric'),
                'tier.integer'           => __('tier.integer'),
                'custom.boolean'         => __('custom.required'),
                'is_active.boolean'      => __('is_active.boolean'),
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
                'plan_id.required'      => __('plan_id.required'),
                'plan_id.uuid'          => __('plan_id.uuid'),
                'plan_id.exists'        => __('plan_id.exists'),
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
                'plan_id.required'      => __('plan_id.required'),
                'plan_id.uuid'          => __('plan_id.uuid'),
                'plan_id.exists'        => __('plan_id.exists'),
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
                'page.integer'  => __('page.integer'),
                'limit.integer' => __('limit.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $plans = $this->planRepository->FindAll($search, (int) $page, (int) $limit);

            if (!$plans) {
                return response()->json(Messages::E400(__('invalid_search_column')), 400);
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
