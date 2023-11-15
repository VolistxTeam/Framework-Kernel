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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createUser(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateCreateValidation($request->all());

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $newUser = $this->userRepository->create($request->all());

            return response()->json(UserDTO::fromModel($newUser)->getDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Update a user.
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return JsonResponse
     */
    public function updateUser(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateCreateValidation(array_merge($request->all(), [
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedUser = $this->userRepository->update($userId, $request->all());

            if (!$updatedUser) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(UserDTO::fromModel($updatedUser)->getDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Delete a user.
     *
     * @param Request $request
     * @param string  $userId
     *
     * @return JsonResponse
     */
    public function deleteUser(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateDeleteValidation([
                'user_id' => $userId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->userRepository->delete($userId);

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
     * @param Request $request
     * @param string  $userId
     *
     * @return JsonResponse
     */
    public function getUser(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateGetValidation([
                'user_id' => $userId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $user = $this->userRepository->find($userId);

            if (!$user) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(UserDTO::fromModel($user)->getDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
