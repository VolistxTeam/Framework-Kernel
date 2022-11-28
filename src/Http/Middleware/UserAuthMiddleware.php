<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

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
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(PersonalTokenRepository $personalTokenRepository, SubscriptionRepository $subscriptionRepository)
    {
        $this->personalTokenRepository = $personalTokenRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $this->personalTokenRepository->AuthPersonalToken($request->bearerToken());

        if (!$token) {
            return response()->json(Messages::E401(), 401);
        }

        PersonalTokens::setToken($token);

        $activeSubscription = $this->subscriptionRepository->FindUserActiveSubscription($token->user_id);

        if (!$activeSubscription) {
            return response()->json(Messages::E401(), 401);
        }

        Subscriptions::setSubscription($activeSubscription);

        Plans::setPlan($activeSubscription->plan);

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
