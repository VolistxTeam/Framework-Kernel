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
use Volistx\FrameworkKernel\RequestParameterValidators\CountryRequestValidationRule;

class PersonalTokenController extends Controller
{
    private PersonalTokenRepository $personalTokenRepository;

    public function __construct(PersonalTokenRepository $personalTokenRepository)
    {
        $this->module = 'personal-tokens';
        $this->personalTokenRepository = $personalTokenRepository;
    }

    public function CreatePersonalToken(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id'         => ['required', 'integer', 'bail'],
                'expires_at'      => ['bail', 'present', 'nullable', 'date'],
                'rate_limit_mode' => ['bail', 'sometimes', new Enum(RateLimitMode::class)],
                'permissions'     => ['bail', 'sometimes', 'array'],
                'permissions.*'   => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule'         => ['bail', 'required', new Enum(AccessRule::class)],
                'ip_range'        => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*'      => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule'    => ['bail', 'required', new Enum(AccessRule::class)],
                'country_range'   => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryRequestValidationRule()],
                'disable_logging' => ['bail', 'sometimes', 'nullable', 'boolean'],
                'hmac_token'      => ['bail', 'sometimes', 'max:255'],
            ], [
                'user_id.required'            => 'The user ID is required.',
                'duration.required'           => 'The duration is required.',
                'expires_at.date'             => 'Expiry date must be a valid date',
                'rate_limit_mode.required'    => 'The rate limit mode is required.',
                'rate_limit_mode.enum'        => 'The rate limit mode must be a valid type.',
                'permissions.array'           => 'The permissions must be an array.',
                'permissions.*.string'        => 'The permissions item must be a string.',
                'ip_rule.enum'                => 'The ip rule must be a valid type.',
                'ip_range.required_if'        => 'The IP range is required when the IP rule is 1 or 2.',
                'ip_range.array'              => 'The IP range must be an array.',
                'ip_range.*.ip'               => 'The IP range item must be a valid IP address.',
                'country_rule.required'       => 'The country rule is required.',
                'country_rule.enum'           => 'The country rule must be a valid type.',
                'country_range.required_if'   => 'The country range is required when the country rule is 1 or 2.',
                'country_range.array'         => 'The country range must be an array.',
                'country_range.*.required_if' => 'The country range item must be a valid country code.',
                'disable_logging.boolean'     => 'The disable logging must be a boolean.',
                'hmac_token.max'              => 'HMac toke should be shorter than 255 letter.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create([
                'user_id'         => $request->input('user_id'),
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

    public function UpdatePersonalToken(Request $request, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'token_id' => $token_id,
            ]), [
                'token_id'        => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
                'expires_at'        => ['bail', 'sometimes', 'date', 'nullable'],
                'permissions'     => ['bail', 'sometimes', 'array'],
                'rate_limit_mode' => ['bail', 'sometimes', new Enum(RateLimitMode::class)],
                'permissions.*'   => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule'         => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'ip_range'        => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*'      => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule'    => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'country_range'   => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryRequestValidationRule()],
                'disable_logging' => ['bail', 'sometimes', 'nullable', 'boolean'],
                'hmac_token'      => ['bail', 'sometimes', 'max:255'],
            ], [
                'token_id.required'           => 'The token ID is required.',
                'token_id.uuid'               => 'The token ID must be a valid uuid.',
                'token_id.exists'             => 'The token with the given ID was not found.',
                'expires_at.date'               => 'Expiry must be a valid date.',
                'permissions.array'           => 'The permissions must be an array.',
                'permissions.*.string'        => 'The permissions item must be a string.',
                'rate_limit_mode.enum'        => 'The rate limit mode must be a valid type.',
                'ip_rule.required'            => 'The ip rule is required.',
                'ip_rule.enum'                => 'The ip rule must be a valid type.',
                'ip_range.required_if'        => 'The IP range is required when the IP rule is 1 or 2.',
                'ip_range.array'              => 'The ID range must be an array.',
                'ip_range.*.ip'               => 'The IP range item must be a valid IP address.',
                'country_rule.required'       => 'The country rule is required.',
                'country_rule.enum'           => 'The country rule must be a valid type.',
                'country_range.required_if'   => 'The country range is required when the country rule is 1 or 2.',
                'country_range.array'         => 'The country range must be an array.',
                'country_range.*.required_if' => 'The country range item must be a valid country code.',
                'disable_logging.boolean'     => 'The disable logging must be a boolean.',
                'hmac_token.max'              => 'HMac toke should be shorter than 255 letter.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $updatedToken = $this->personalTokenRepository->Update($token_id, $request->all());
            if (!$updatedToken) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(PersonalTokenDTO::fromModel($updatedToken)->GetDTO());
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function ResetPersonalToken(Request $request, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'reset')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'token_id' => $token_id,
            ]), [
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ], [
                'token_id.required' => 'The token ID is required.',
                'token_id.uuid'     => 'The token ID must be a valid uuid.',
                'token_id.exists'   => 'The token ID does not found in the database.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $resetToken = $this->personalTokenRepository->Reset(
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

    public function DeletePersonalToken(Request $request, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }
            $validator = Validator::make(array_merge($request->all(), [
                'token_id' => $token_id,
            ]), [
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ], [
                'token_id.required' => 'The token ID is required.',
                'token_id.uuid'     => 'The token ID must be a valid uuid.',
                'token_id.exists'   => 'The token ID does not found in the database.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $result = $this->personalTokenRepository->Delete($token_id);
            if (!$result) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetPersonalToken(Request $request, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'token_id' => $token_id,
            ]), [
                'token_id' => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ], [
                'token_id.required' => 'The token ID is required.',
                'token_id.uuid'     => 'The token ID must be a valid uuid.',
                'token_id.exists'   => 'The token with the given ID was not found.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $token = $this->personalTokenRepository->Find($token_id);

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

            $validator = Validator::make([
                'page'  => $page,
                'limit' => $limit,
            ], [
                'page'  => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $tokens = $this->personalTokenRepository->FindAll($search, $page, $limit);

            if (!$tokens) {
                return response()->json(Messages::E400('Invalid search column'), 400);
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

    public function Sync(Request $request)
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'sync')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['required', 'integer', 'bail'],
            ], [
                'user_id.required' => 'The user ID is required.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $this->personalTokenRepository->DeleteHiddenTokens($request->input('user_id'));

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create([
                'user_id'         => $request->input('user_id'),
                'key'             => $saltedKey['key'],
                'salt'            => $saltedKey['salt'],
                'permissions'     => ['*'],
                'ip_rule'         => AccessRule::NONE,
                'ip_range'        => [],
                'country_rule'    => AccessRule::NONE,
                'country_range'   => [],
                'activated_at'    => Carbon::now(),
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
