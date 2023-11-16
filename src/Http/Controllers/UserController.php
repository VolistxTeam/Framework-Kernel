<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\DataTransferObjects\UserDTO;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\UserRepository;

class UserController extends Controller
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->module = 'user';
        $this->userRepository = $userRepository;
    }

    /**
     * Create a new user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function CreateUser(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateCreateValidation($request->all());

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newUser = $this->userRepository->Create($request->all());

            return response()->json(UserDTO::fromModel($newUser)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Update a user.
     *
     * @param  Request  $request
     * @param  string  $userId
     * @return JsonResponse
     */
    public function UpdateUser(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateCreateValidation(array_merge($request->all(), [
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedUser = $this->userRepository->Update($userId, $request->all());

            if (!$updatedUser) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(UserDTO::fromModel($updatedUser)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Delete a user.
     *
     * @param  Request  $request
     * @param  string  $userId
     * @return JsonResponse
     */
    public function DeleteUser(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateDeleteValidation([
                'user_id' => $userId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->userRepository->Delete($userId);

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

    /**
     * Get a user.
     *
     * @param  Request  $request
     * @param  string  $userId
     * @return JsonResponse
     */
    public function GetUser(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateGetValidation([
                'user_id' => $userId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $user = $this->userRepository->Find($userId);

            if (!$user) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(UserDTO::fromModel($user)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}