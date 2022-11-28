<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AdminAuthValidationRules\AdminIPValidationRule;
use function response;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\AccessTokenRepository;
use Volistx\FrameworkKernel\UserAuthValidationRules\IPValidationRule;

class AdminAuthMiddleware
{
    private AccessTokenRepository $accessTokenRepository;

    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

        if (!$token) {
            return response()->json(Messages::E401(), 401);
        }

        AccessTokens::setToken($token);

        //add extra validators in the required order.
        //To be refactored to detect all classes with a base of ValidationRuleBase and create instance of them passing parameters, and ordering them by id
        $validators = [
            new AdminIPValidationRule($request),
        ];

        foreach ($validators as $validator) {
            $result = $validator->validate();
            if ($result !== true) {
                return response()->json($result['message'], $result['code']);
            }
        }

        AccessTokens::setToken($token);

        return $next($request);
    }
}
