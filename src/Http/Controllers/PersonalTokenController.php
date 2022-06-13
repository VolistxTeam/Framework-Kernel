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
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Keys;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;
use Volistx\FrameworkKernel\RequestValidators\CountryRequestValidationRule;

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
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'create')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'duration'        => ['bail', 'required', 'integer'],
                'permissions'     => ['bail', 'sometimes', 'array'],
                'permissions.*'   => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule'         => ['bail', 'required', new Enum(AccessRule::class)],
                'ip_range'        => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*'      => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule'    => ['bail', 'required', new Enum(AccessRule::class)],
                'country_range'   => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryRequestValidationRule()],
            ],[
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.exists' => 'The subscription with the given ID was not found.',
                'duration.required'      => 'The duration is required.',
                'duration.integer'       => 'The duration must be an integer.',
                'permissions.array'      => 'The permissions must be an array.',
                'permissions.*.string'   => 'The permissions item must be a string.',
                'ip_rule.required'        => 'The ip rule is required.',
                'ip_rule.enum'             => 'The ip rule must be a valid type.',
                'ip_range.required_if'  => 'The IP range is required when the IP rule is 1 or 2.',
                'ip_range.array'           => 'The IP range must be an array.',
                'ip_range.*.ip'         => 'The IP range item must be a valid IP address.',
                'country_rule.required' => 'The country rule is required.',
                'country_rule.enum'      => 'The country rule must be a valid type.',
                'country_range.required_if' => 'The country range is required when the country rule is 1 or 2.',
                'country_range.array'      => 'The country range must be an array.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create($subscription_id, [
                'key'             => $saltedKey['key'],
                'salt'            => $saltedKey['salt'],
                'permissions'     => $request->input('permissions') ?? [],
                'ip_rule'         => $request->input('ip_rule') ?? AccessRule::NONE,
                'ip_range'        => $request->input('ip_range') ?? [],
                'country_rule'    => $request->input('country_rule') ?? AccessRule::NONE,
                'country_range'   => $request->input('country_range') ?? [],
                'activated_at'    => Carbon::now(),
                'duration'        => $request->input('duration'),
                'hidden'          => false,
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO($saltedKey['key']), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500($ex), 500);
        }
    }

    public function UpdatePersonalToken(Request $request, $subscription_id, $token_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'update')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id'        => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id'        => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
                'duration'        => ['bail', 'sometimes', 'integer'],
                'permissions'     => ['bail', 'sometimes', 'array'],
                'permissions.*'   => ['bail', 'required_if:permissions,array', 'string'],
                'ip_rule'         => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'ip_range'        => ['bail', 'required_if:ip_rule,1,2', 'array'],
                'ip_range.*'      => ['bail', 'required_if:ip_rule,1,2', 'ip'],
                'country_rule'    => ['bail', 'sometimes', new Enum(AccessRule::class)],
                'country_range'   => ['bail', 'required_if:ip_rule,1,2', 'array', new CountryRequestValidationRule()],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid uuid.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
                'token_id.required'        => 'The token ID is required.',
                'token_id.uuid'            => 'The token ID must be a valid uuid.',
                'token_id.exists'          => 'The token with the given ID was not found.',
                'duration.required'      => 'The duration is required.',
                'duration.integer'       => 'The duration must be an integer.',
                'permissions.array'      => 'The permissions must be an array.',
                'permissions.*.string'   => 'The permissions item must be a string.',
                'ip_rule.required'        => 'The ip rule is required.',
                'ip_rule.enum'             => 'The ip rule must be a valid type.',
                'ip_range.required_if'  => 'The IP range is required when the IP rule is 1 or 2.',
                'ip_range.array'           => 'The ID range must be an array.',
                'ip_range.*.ip'         => 'The IP range item must be a valid IP address.',
                'country_rule.required' => 'The country rule is required.',
                'country_rule.enum'      => 'The country rule must be a valid type.',
                'country_range.required_if' => 'The country range is required when the country rule is 1 or 2.',
                'country_range.array'      => 'The country range must be an array.',
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
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'reset')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id'        => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id'        => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid uuid.',
                'subscription_id.exists'   => 'The subscription ID does not found in the database.',
                'token_id.required'        => 'The token ID is required.',
                'token_id.uuid'            => 'The token ID must be a valid uuid.',
                'token_id.exists'          => 'The token ID does not found in the database.',
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
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'delete')) {
                return response()->json(Messages::E401(), 401);
            }
            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id'        => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id'        => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid uuid.',
                'subscription_id.exists'   => 'The subscription ID does not found in the database.',
                'token_id.required'        => 'The token ID is required.',
                'token_id.uuid'            => 'The token ID must be a valid uuid.',
                'token_id.exists'          => 'The token ID does not found in the database.',
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
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
                'token_id'        => $token_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
                'token_id'        => ['required', 'uuid', 'bail', 'exists:personal_tokens,id'],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid uuid.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
                'token_id.required'        => 'The token ID is required.',
                'token_id.uuid'            => 'The token ID must be a valid uuid.',
                'token_id.exists'          => 'The token with the given ID was not found.',
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
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid uuid.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
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

            $tokens = $this->personalTokenRepository->FindAll($subscription_id, $search, $page, $limit);

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

    public function Sync(Request $request, $subscription_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'sync')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'subscription_id' => $subscription_id,
            ]), [
                'subscription_id' => ['required', 'uuid', 'bail', 'exists:subscriptions,id'],
            ], [
                'subscription_id.required' => 'The subscription ID is required.',
                'subscription_id.uuid'     => 'The subscription ID must be a valid uuid.',
                'subscription_id.exists'   => 'The subscription with the given ID was not found.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $this->personalTokenRepository->DeleteHiddenTokens($subscription_id);

            $saltedKey = Keys::randomSaltedKey();

            $newPersonalToken = $this->personalTokenRepository->Create($subscription_id, [
                'key'             => $saltedKey['key'],
                'salt'            => $saltedKey['salt'],
                'permissions'     => ['*'],
                'ip_rule'         => AccessRule::NONE,
                'activated_at'    => Carbon::now(),
                'duration'        => -1,
                'hidden'          => true,
            ]);

            return response()->json(PersonalTokenDTO::fromModel($newPersonalToken)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
