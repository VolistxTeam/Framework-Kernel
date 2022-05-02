<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;

class UserAuthMiddleware
{
    private PersonalTokenRepository $personalTokenRepository;

    public function __construct(PersonalTokenRepository $personalTokenRepository)
    {
        $this->personalTokenRepository = $personalTokenRepository;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $this->personalTokenRepository->AuthPersonalToken($request->bearerToken());

        if (!$token) {
            return response()->json(Messages::E401(), 401);
        }

        $plan = $token->subscription()->first()->plan()->first();

        if (!$plan) {
            return response()->json(Messages::E401(), 401);
        }

        //prepare inputs array
        $inputs = [
            'request' => $request,
            'token' => $token,
            'plan' => $plan,
        ];

        $validatorClasses = config('volistx.validators');

        $validators = [];

        foreach ($validatorClasses as $item) {
            $validators[] = new $item($inputs);
        }

        foreach ($validators as $validator) {
            $result = $validator->validate();
            if ($result !== true) {
                return response()->json($result['message'], $result['code']);
            }
        }

        AccessTokens::setToken($token);
        Plans::setPlan($plan);

        return $next($request);
    }
}
