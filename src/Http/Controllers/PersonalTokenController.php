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
use Volistx\FrameworkKernel\Enums\RateLimitMode;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Keys;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;
use Volistx\FrameworkKernel\Rules\CountryRequest;

class PersonalTokenController extends Controller
{
    private PersonalTokenRepository $personalTokenRepository;

    public function __construct(PersonalTokenRepository $personalTokenRepository)
    {
        $this->module = 'personal-tokens';
        $this->personalTokenRepository = $personalTokenRepository;
    }

    public function CreatePersonalToken(Request $request, $user_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'user_id' => $user_id,
            ]), [
                'user_id'         => ['required', 'integer', 'bail', 'exists:users,id'],
                'expires_at'      => ['bail', 'present', 'nullable', 'date'],
                'rate_limit_mode' => ['bail', 'sometimes', new Enum(RateLimitMode::class)],
                'permissions'     => ['bail', 'sometimes', 'array'],
                'permissions.*'   => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule'         => ['bail', 'required', new Enum(AccessRule::class)],
                'ip_range'        => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*'      => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule'    => ['bail', 'required', new Enum(AccessRule::class)],
                'country_range'   => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryRequest()],
                'disable_logging' => ['bail', 'sometimes', 'nullable', 'boolean'],
                'hmac_token'      => ['bail', 'sometimes', 'max:255'],
            ], [
                'user_id.required'             => trans('volistx::user_id.required'),
                'user_id.integer'              => trans('volistx::user_id.integer'),
                'user_id.exists'               => trans('volistx::user_id.exists'),
                'duration.required'            => trans('volistx::duration.required'),
                'expires_at.date'              => trans('volistx::expires_at.date'),
                'rate_limit_mode.required'     => trans('volistx::rate_limit_mode.required'),
                'rate_limit_mode.enum'         => trans('volistx::rate_limit_mode.enum'),
                'permissions.array'            => trans('volistx::permissions.array'),
                'permissions.*.string'         => trans('volistx::permissions.*.string'),
                'ip_rule.enum'                 => trans('volistx::ip_rule.enum'),
                'ip_range.required_if'         => trans('volistx::ip_range.required_if'),
                'ip_range.array'               => trans('volistx::ip_range.array'),
                'ip_range.*.ip'                => trans('volistx::ip_range.*.ip'),
                'country_rule.required'        => trans('volistx::country_rule.required'),
                'country_rule.enum'            => trans('volistx::country_rule.enum'),
                'country_range.required_if'    => trans('volistx::country_range.required_if'),
                'country_range.array'          => trans('volistx::country_range.array'),
                'country_range.*.required_if'  => trans('volistx::country_range.*.required_if'),
                'disable_logging.boolean'      => trans('volistx::disable_logging.boolean'),
                'hmac_token.max'               => trans('volistx::hmac_token.max'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create([
                'user_id'         => $user_id,
                'key'             => $saltedKey['key'],
                'salt'            => $saltedKey['salt'],
                'rate_limit_mode' => $request->input('rate_limit_mode') ?? RateLimitMode::SUBSCRIPTION,
                'permissions'     => $request->input('permissions') ?? [],
                'ip_rule'         => $request->input('ip_rule') ?? AccessRule::NONE,
                'ip_range'        => $request->input('ip_range') ?? [],
                'country_rule'    => $request->input('country_rule') ?? AccessRule::NONE,
                'country_range'   => $request->input('country_range') ?? [],
                'activated_at'    => Carbon::now(),
                'expires_at'      => $request->input('expires_at'),
                'hidden'          => false,
                'disable_logging' => $request->input('disable_logging') ?? false,
                'hmac_token'      => $request->input('hmac_token') ?? Keys::randomKey(32),
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO($saltedKey['key']), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500($ex), 500);
        }
    }

    public function UpdatePersonalToken(Request $request, $user_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'token_id'        => $token_id,
                'user_id'         => $user_id,
            ]), [
                'token_id'          => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
                'user_id'           => ['bail', 'required', 'integer', 'exists:users,id'],
                'expires_at'        => ['bail', 'sometimes', 'date', 'nullable'],
                'permissions'       => ['bail', 'sometimes', 'array'],
                'rate_limit_mode'   => ['bail', 'sometimes', new Enum(RateLimitMode::class)],
                'permissions.*'     => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule'           => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'ip_range'          => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*'        => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule'      => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'country_range'     => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryRequest()],
                'disable_logging'   => ['bail', 'sometimes', 'nullable', 'boolean'],
                'hmac_token'        => ['bail', 'sometimes', 'max:255'],
            ], [
                'token_id.required'             => trans('volistx::token_id.required'),
                'token_id.uuid'                 => trans('volistx::token_id.uuid'),
                'token_id.exists'               => trans('volistx::token_id.exists'),
                'user_id.required'              => trans('volistx::user_id.required'),
                'user_id.integer'               => trans('volistx::user_id.integer'),
                'user_id.exists'                => trans('volistx::user_id.exists'),
                'expires_at.date'               => trans('volistx::expires_at.date'),
                'permissions.array'             => trans('volistx::permissions.array'),
                'permissions.*.string'          => trans('volistx::permissions.*.string'),
                'rate_limit_mode.enum'          => trans('volistx::rate_limit_mode.enum'),
                'ip_rule.required'              => trans('volistx::ip_rule.required'),
                'ip_rule.enum'                  => trans('volistx::ip_rule.enum'),
                'ip_range.required_if'          => trans('volistx::ip_range.required_if'),
                'ip_range.array'                => trans('volistx::ip_range.array'),
                'ip_range.*.ip'                 => trans('volistx::ip_range.*.ip'),
                'country_rule.required'         => trans('volistx::country_rule.required'),
                'country_rule.enum'             => trans('volistx::country_rule.enum'),
                'country_range.required_if'     => trans('volistx::country_range.required_if'),
                'country_range.array'           => trans('volistx::country_range.array'),
                'country_range.*.required_if'   => trans('volistx::country_range.*.required_if'),
                'disable_logging.boolean'       => trans('volistx::disable_logging.boolean'),
                'hmac_token.max'                => trans('volistx::hmac_token.max'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedToken = $this->personalTokenRepository->Update($user_id, $token_id, $request->all());
            if (!$updatedToken) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($updatedToken)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function ResetPersonalToken(Request $request, $user_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'reset')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'token_id' => $token_id,
                'user_id'  => $user_id,

            ]), [
                'token_id' => ['bail', 'required', 'uuid', 'exists:personal_tokens,id'],
                'user_id'  => ['bail', 'required', 'integer', 'exists:users,id'],

            ], [
                'token_id.required'             => trans('volistx::token_id.required'),
                'token_id.uuid'                 => trans('volistx::token_id.uuid'),
                'token_id.exists'               => trans('volistx::token_id.exists'),
                'user_id.required'              => trans('volistx::user_id.required'),
                'user_id.integer'               => trans('volistx::user_id.integer'),
                'user_id.exists'                => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $resetToken = $this->personalTokenRepository->Reset(
                $user_id,
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

    public function DeletePersonalToken(Request $request, $user_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }
            $validator = Validator::make(array_merge($request->all(), [
                'token_id' => $token_id,
                'user_id'  => $user_id,

            ]), [
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
                'user_id'  => ['bail', 'required', 'integer', 'exists:users,id'],

            ], [
                'token_id.required'             => trans('volistx::token_id.required'),
                'token_id.uuid'                 => trans('volistx::token_id.uuid'),
                'token_id.exists'               => trans('volistx::token_id.exists'),
                'user_id.required'              => trans('volistx::user_id.required'),
                'user_id.integer'               => trans('volistx::user_id.integer'),
                'user_id.exists'                => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->personalTokenRepository->Delete($user_id, $token_id);
            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPersonalToken(Request $request, $user_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'token_id'        => $token_id,
                'user_id'         => $user_id,

            ]), [
                'token_id'        => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
                'user_id'         => ['bail', 'required', 'integer', 'exists:users,id'],

            ], [
                'token_id.required'             => trans('volistx::token_id.required'),
                'token_id.uuid'                 => trans('volistx::token_id.uuid'),
                'token_id.exists'               => trans('volistx::token_id.exists'),
                'user_id.required'              => trans('volistx::user_id.required'),
                'user_id.integer'               => trans('volistx::user_id.integer'),
                'user_id.exists'                => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $token = $this->personalTokenRepository->Find($user_id, $token_id);

            if (!$token) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($token)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPersonalTokens(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = Validator::make(
                [
                    'page'  => $page,
                    'limit' => $limit,
                ],
                [
                    'page'  => ['bail', 'sometimes', 'integer'],
                    'limit' => ['bail', 'sometimes', 'integer'],
                ],
                [
                    'page.integer'  => trans('volistx::page.integer'),
                    'limit.integer' => trans('volistx::limit.integer'),
                ]
            );

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
                    'current'  => $tokens->currentPage(),
                    'total'    => $tokens->lastPage(),
                ],
                'items' => $userTokens,
            ]);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function Sync(Request $request, $user_id)
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'sync')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'user_id'=> $user_id,
            ], [
                'user_id' => ['required', 'integer', 'bail', 'exists:users,id'],
            ], [
                'user_id.required'         => trans('volistx::user_id.required'),
                'user_id.integer'          => trans('volistx::user_id.integer'),
                'user_id.exists'           => trans('volistx::user_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $this->personalTokenRepository->DeleteHiddenTokens($user_id);

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create([
                'user_id'         => $user_id,
                'key'             => $saltedKey['key'],
                'salt'            => $saltedKey['salt'],
                'permissions'     => ['*'],
                'ip_rule'         => AccessRule::NONE,
                'ip_range'        => [],
                'country_rule'    => AccessRule::NONE,
                'country_range'   => [],
                'activated_at'    => Carbon::now(),
                'expires_at'      => null,
                'duration'        => null,
                'hidden'          => true,
                'disable_logging' => true,
                'rate_limit_mode' => RateLimitMode::SUBSCRIPTION,
                'hmac_token'      => Keys::randomKey(32),
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO($saltedKey['key']), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
