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

        Plans::setPlan($activeSubscription->plan) ;

        //Request Validators : they are validating the request .. and they dont change in the base. invalid request shouldn't be anything in db
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

        //Request Pre Processors : request is valid. but it needs to be pre-processed before executing it,
        // it can change database .. they can return false to prevent proceeding with request

        //Note : Curently, we have a single pre processor , so its not required to re-fetch/upate entities after passing one preprocessor..
        //if we had more, we should update entities so preprocessors can work with updated info

        $preprocessorsClasses = config('volistx.preprocessors');

        $preProcessors = [];

        foreach ($preprocessorsClasses as $preprocessorsClass) {
            //prob need to change if we had more than single processor.
            $preProcessors[] = new $preprocessorsClass($request);
        }

        foreach ($preProcessors as $processor) {
            $result = $processor->Process();
            if ($result !== true) {
                return response()->json($result['message'], $result['code']);
            }
        }

        return $next($request);
    }
}
