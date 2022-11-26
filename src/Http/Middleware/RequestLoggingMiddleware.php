<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Subscriptions;
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
            if (PersonalTokens::getToken()->disable_logging === false) {
//                $inputs = [
//                    'url'             => $request->fullUrl(),
//                    'method'          => $request->method(),
//                    'ip'              => $request->ip(),
//                    'user_agent'      => $request->userAgent() ?? null,
//                    'subscription_id' => PersonalTokens::getToken()->subscription()->first()->id,
//                ];
//                $this->userLoggingService->CreateUserLog($inputs);
                Event::dispatch(
                    new UserRequestCompleted(
                    $request->fullUrl(),
                    $request->method(),
                    $request->ip(),
                    $request->userAgent() ?? null,
                    Subscriptions::getSubscription()->id
                )
                );
            }
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
