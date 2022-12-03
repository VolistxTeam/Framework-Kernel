<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Volistx\FrameworkKernel\DataTransferObjects\PlanDTO;
use Volistx\FrameworkKernel\DataTransferObjects\UserDTO;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\PlanRepository;
use Volistx\FrameworkKernel\Repositories\UserRepository;

class UserController extends Controller
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->module = 'user';
        $this->userRepository = $userRepository;
    }

    public function CreateUser(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['bail', 'required', 'integer'],
            ], [
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $new_user = $this->userRepository->Create($request->all());

            return response()->json(UserDTO::fromModel($new_user)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function UpdateUser(Request $request, $user_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['bail', 'required', 'integer'],
                'is_active' => ['bail', 'sometimes', 'boolean'],
            ], [
                'user_id.required' => trans('volistx::user_id.required'),
                'user_id.integer' => trans('volistx::user_id.integer'),
                'is_active.boolean' => trans('volistx::is_active.boolean'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updated_user = $this->userRepository->Update($user_id, $request->all());

            if (!$updated_user) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(UserDTO::fromModel($updated_user)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function DeleteUser(Request $request, $user_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['bail', 'required', 'integer'],
            ], [
                'user_id.required' => trans('volistx::user_id.required'),
            ]);


            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->userRepository->Delete($user_id);
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

    public function GetUser(Request $request, $user_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['bail', 'required', 'integer'],
            ], [
                'user_id.required' => trans('volistx::user_id.required'),
            ]);


            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $user = $this->userRepository->Find($user_id);

            if (!$user) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(UserDTO::fromModel($user)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
