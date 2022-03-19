<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\AccessTokenRepository;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;
use Volistx\FrameworkKernel\ValidationRules\IPValidationRule;
use Volistx\FrameworkKernel\ValidationRules\ValidKeyValidationRule;

class AuthMiddleware
{
    private AccessTokenRepository $accessTokenRepository;
    private PersonalTokenRepository $personalTokenRepository;

    public function __construct(AccessTokenRepository $accessTokenRepository, PersonalTokenRepository $personalTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->personalTokenRepository = $personalTokenRepository;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->route()->getPrefix() == 'sys-bin') {
            $result = $this->AuthAdmin($request);
            if ($result === true) {
                return $next($request);
            } else {
                return response()->json($result['message'], $result['code']);
            }
        } else {
            if (!$request->header('SF-Connection-Secret') && $request->header('SF-Connection-Secret') !== config('volistx.sf-connection-secret')) {
                return response()->json(Messages::E401(), 401);
            }

            if ($request->header('SF-Subscription-ID')) {
                $result = $this->AuthAdmin($request);
                if ($result === true) {
                    return $next($request);
                } else {
                    return response()->json($result['message'], $result['code']);
                }
            } else {
                $result = $this->AuthUser($request);
                if ($result === true) {
                    return $next($request);
                } else {
                    return response()->json($result['message'], $result['code']);
                }
            }
        }
    }

    private function AuthAdmin(Request $request)
    {
        $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

        $inputs = [
            'request' => $request,
            'token'   => $token,
        ];

        $validators = [
            new ValidKeyValidationRule($inputs),
            new IPValidationRule($inputs),
        ];

        foreach ($validators as $validator) {
            $result = $validator->validate();
            if ($result !== true) {
                return $result;
            }
        }

        $request->merge([
            'X_ACCESS_TOKEN' => $token,
        ]);

        return true;
    }

    private function AuthUser($request)
    {
        $token = $this->personalTokenRepository->AuthPersonalToken($request->bearerToken());

        if (!$token) {
            return [
                'message' => Messages::E401(),
                'code'    => 401,
            ];
        }

        $plan = $token->subscription()->first()->plan()->first();

        if (!$plan) {
            return [
                'message' => Messages::E401(),
                'code'    => 401,
            ];
        }

        //prepare inputs array
        $inputs = [
            'request' => $request,
            'token'   => $token,
            'plan'    => $plan,
        ];

        $validatorClasses = config('volistx.validators');

        $validators = [];

        foreach ($validatorClasses as $item) {
            $validators[] = new $item($inputs);
        }

        foreach ($validators as $validator) {
            $result = $validator->validate();
            if ($result !== true) {
                return $result;
            }
        }

        $request->merge([
            'X_PERSONAL_TOKEN' => $token,
            'PLAN'             => $plan,
        ]);

        return true;
    }
}
