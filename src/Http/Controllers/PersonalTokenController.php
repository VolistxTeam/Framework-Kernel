<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Volistx\FrameworkKernel\DataTransferObjects\PersonalTokenDTO;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Keys;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;
use Volistx\FrameworkKernel\RequestValidators\CountryValidationRule;

class PersonalTokenController extends Controller
{
    private PersonalTokenRepository $personalTokenRepository;

    public function __construct(PersonalTokenRepository $personalTokenRepository)
    {
        $this->module = 'personal-tokens';
        $this->personalTokenRepository = $personalTokenRepository;
    }

    public function CreatePersonalToken(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'hours_to_expire' => ['bail', 'required', 'integer'],
                'permissions' => ['bail', 'sometimes', 'array'],
                'permissions.*' => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule' => ['bail', 'required', new Enum(AccessRule::class)],
                'ip_range' => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*' => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule' => ['bail', 'required', new Enum(AccessRule::class)],
                'country_range' => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryValidationRule()],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create($subscription_id, [
                'key' => $saltedKey['key'],
                'salt' => $saltedKey['salt'],
                'permissions' => $request->input('permissions'),
                'ip_rule' => $request->input('ip_rule'),
                'ip_range' => $request->input('ip_range'),
                'country_rule' => $request->input('country_rule'),
                'country_range' => $request->input('country_range'),
                'activated_at' => Carbon::now(),
                'hours_to_expire' => $request->input('hours_to_expire'),
                'hidden' => false,
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO($saltedKey['key']), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function UpdatePersonalToken(Request $request, $subscription_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id' => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
                'hours_to_expire' => ['bail', 'sometimes', 'integer'],
                'permissions' => ['bail', 'sometimes', 'array'],
                'permissions.*' => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule' => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'ip_range' => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*' => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule' => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'country_range' => ['bail', 'sometimes', 'array', new CountryValidationRule()],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedToken = $this->personalTokenRepository->Update($subscription_id, $token_id, $request->all());
            if (!$updatedToken) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($updatedToken)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function ResetPersonalToken(Request $request, $subscription_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'reset')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id' => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $resetToken = $this->personalTokenRepository->Reset(
                $subscription_id,
                $token_id,
                $saltedKey
            );

            if (!$resetToken) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($resetToken)->GetDTO($saltedKey['key']));
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function DeletePersonalToken(Request $request, $subscription_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }
            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id' => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->personalTokenRepository->Delete($subscription_id, $token_id);
            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPersonalToken(Request $request, $subscription_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id' => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $token = $this->personalTokenRepository->Find($subscription_id, $token_id);

            if (!$token) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($token)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPersonalTokens(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = Validator::make([
                'page' => $page,
                'limit' => $limit,
            ], [
                'page' => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $tokens = $this->personalTokenRepository->FindAll($subscription_id, $search, $page, $limit);

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

    public function Sync(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check($request->X_ACCESS_TOKEN, $this->module, 'sync')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $this->personalTokenRepository->DeleteHiddenTokens($subscription_id);

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create($subscription_id, [
                'key' => $saltedKey['key'],
                'salt' => $saltedKey['salt'],
                'permissions' => ['*'],
                'ip_rule' => AccessRule::NONE,
                'activated_at' => Carbon::now(),
                'hours_to_expire' => -1,
                'hidden' => true,
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
