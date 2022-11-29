<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;
use Volistx\FrameworkKernel\Repositories\SubscriptionRepository;

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

        PersonalTokens::setToken($token);

        $validatorClasses = config('volistx.validators');

        $validators = [];

        foreach ($validatorClasses as $validatorClass) {
            $validators[] = new $validatorClass($request);
        }

        foreach ($validators as $validator) {
            $result = $validator->validate();
            if ($result !== true) {
                return response()->json($result['message'], $result['code']);
            }
        }

        return $next($request);
    }
}
