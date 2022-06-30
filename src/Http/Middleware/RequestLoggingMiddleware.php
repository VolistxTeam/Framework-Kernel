<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class RequestLoggingMiddleware
{
    private IAdminLoggingService $adminLoggingService;
    private IUserLoggingService $userLoggingService;

    public function __construct(IAdminLoggingService $adminLoggingService, IUserLoggingService $userLoggingService)
    {
        $this->adminLoggingService = $adminLoggingService;
        $this->userLoggingService = $userLoggingService;
    }

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (PersonalTokens::getToken() && PersonalTokens::getToken()->hidden === false) {
            $inputs = [
                'url'             => $request->fullUrl(),
                'method'          => $request->method(),
                'ip'              => $request->ip(),
                'user_agent'      => $request->userAgent() ?? null,
                'subscription_id' => PersonalTokens::getToken()->subscription()->first()->id,
            ];
            $this->userLoggingService->CreateUserLog($inputs);
        } elseif (AccessTokens::getToken()) {
            $inputs = [
                'url'             => $request->fullUrl(),
                'method'          => $request->method(),
                'ip'              => $request->ip(),
                'user_agent'      => $request->userAgent() ?? null,
                'access_token_id' => AccessTokens::getToken()->id,
            ];
            $this->adminLoggingService->CreateAdminLog($inputs);
        }
    }
}
