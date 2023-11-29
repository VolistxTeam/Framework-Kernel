<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\DataTransferObjects\PersonalTokenDTO;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Keys;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;

class PersonalTokenController extends Controller
{
    private PersonalTokenRepository $personalTokenRepository;

    /**
     * PersonalTokenController constructor.
     *
     * @param PersonalTokenRepository $personalTokenRepository The personal token repository
     */
    public function __construct(PersonalTokenRepository $personalTokenRepository)
    {
        $this->module = 'personal-tokens';
        $this->personalTokenRepository = $personalTokenRepository;
    }

    /**
     * Create a personal token.
     *
     * @param Request $request The HTTP request
     * @param string $userId The user ID
     *
     * @return JsonResponse The JSON response
     */
    public function CreatePersonalToken(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateCreateValidation(array_merge($request->all(), [
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();
            $newPersonalToken = $this->personalTokenRepository->Create([
                'user_id' => $userId,
                'name' => $request->input('name') ?? '',
                'key' => $saltedKey['key'],
                'salt' => $saltedKey['salt'],
                'rate_limit_mode' => $request->input('rate_limit_mode') ?? RateLimitMode::SUBSCRIPTION,
                'permissions' => $request->input('permissions') ?? [],
                'ip_rule' => $request->input('ip_rule') ?? AccessRule::NONE,
                'ip_range' => $request->input('ip_range') ?? [],
                'country_rule' => $request->input('country_rule') ?? AccessRule::NONE,
                'country_range' => $request->input('country_range') ?? [],
                'activated_at' => Carbon::now(),
                'expires_at' => $request->input('expires_at'),
                'hidden' => false,
                'disable_logging' => $request->input('disable_logging') ?? false,
                'hmac_token' => $request->input('hmac_token') ?? Keys::randomKey(32),
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO($saltedKey['key']), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500($ex), 500);
        }
    }

    /**
     * Update a personal token.
     *
     * @param Request $request The HTTP request
     * @param string $userId The user ID
     * @param string $tokenId The token ID
     *
     * @return JsonResponse The JSON response
     */
    public function UpdatePersonalToken(Request $request, string $userId, string $tokenId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateUpdateValidation(array_merge($request->all(), [
                'token_id' => $tokenId,
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedToken = $this->personalTokenRepository->Update($userId, $tokenId, $request->all());

            if (!$updatedToken) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($updatedToken)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Reset a personal token.
     *
     * @param Request $request The HTTP request
     * @param string $userId The user ID
     * @param string $tokenId The token ID
     *
     * @return JsonResponse The JSON response
     */
    public function ResetPersonalToken(Request $request, string $userId, string $tokenId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'reset')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateResetValidation(array_merge($request->all(), [
                'token_id' => $tokenId,
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();
            $resetToken = $this->personalTokenRepository->Reset($userId, $tokenId, $saltedKey);

            if (!$resetToken) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($resetToken)->GetDTO($saltedKey['key']));
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Delete a personal token.
     *
     * @param Request $request The HTTP request
     * @param string $userId The user ID
     * @param string $tokenId The token ID
     *
     * @return JsonResponse The JSON response
     */
    public function DeletePersonalToken(Request $request, string $userId, string $tokenId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateDeleteValidation(array_merge($request->all(), [
                'token_id' => $tokenId,
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->personalTokenRepository->Delete($userId, $tokenId);

            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Get a personal token.
     *
     * @param Request $request The HTTP request
     * @param string $userId The user ID
     * @param string $tokenId The token ID
     *
     * @return JsonResponse The JSON response
     */
    public function GetPersonalToken(Request $request, string $userId, string $tokenId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateGetValidation(array_merge($request->all(), [
                'token_id' => $tokenId,
                'user_id' => $userId,
            ]));

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $token = $this->personalTokenRepository->Find($userId, $tokenId);

            if (!$token) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($token)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Get all personal tokens.
     *
     * @param Request $request The HTTP request
     *
     * @return JsonResponse The JSON response
     */
    public function GetPersonalTokens(Request $request): JsonResponse
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

            $tokens = $this->personalTokenRepository->FindAll($search, $page, $limit);

            if (!$tokens) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
            }

            $userTokens = [];

            foreach ($tokens->items() as $item) {
                $userTokens[] = PersonalTokenDTO::fromModel($item)->GetDTO();
            }

            return response()->json([
                'pagination' => [
                    'per_page' => $tokens->perPage(),
                    'current' => $tokens->currentPage(),
                    'total' => $tokens->lastPage(),
                ],
                'items' => $userTokens,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Sync personal tokens.
     *
     * @param Request $request The HTTP request
     * @param string $userId The user ID
     *
     * @return JsonResponse The JSON response
     */
    public function Sync(Request $request, string $userId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'sync')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->GetModuleValidation($this->module)->generateSyncValidation([
                'user_id' => $userId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $this->personalTokenRepository->DeleteHiddenTokens($userId);
            $saltedKey = Keys::randomSaltedKey();
            $newPersonalToken = $this->personalTokenRepository->Create([
                'name'    => 'system_key',
                'user_id' => $userId,
                'key' => $saltedKey['key'],
                'salt' => $saltedKey['salt'],
                'permissions' => ['*'],
                'ip_rule' => AccessRule::NONE,
                'ip_range' => [],
                'country_rule' => AccessRule::NONE,
                'country_range' => [],
                'activated_at' => Carbon::now(),
                'expires_at' => null,
                'duration' => null,
                'hidden' => true,
                'disable_logging' => true,
                'rate_limit_mode' => RateLimitMode::SUBSCRIPTION,
                'hmac_token' => Keys::randomKey(32),
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO($saltedKey['key']), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}