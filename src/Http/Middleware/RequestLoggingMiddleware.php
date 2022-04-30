<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
        if ($request->toArray()['X_PERSONAL_TOKEN'] ?? false) {
            $inputs = [
                'url'             => $request->fullUrl(),
                'method'          => $request->method(),
                'ip'              => $request->ip(),
                'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'subscription_id' => $request->toArray()['X_PERSONAL_TOKEN']->subscription()->first()->id,
            ];
            $this->userLoggingService->CreateUserLog($inputs);
        } elseif ($request->toArray()['X_ACCESS_TOKEN'] ?? false) {
            $inputs = [
                'url'             => $request->fullUrl(),
                'method'          => $request->method(),
                'ip'              => $request->ip(),
                'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'access_token_id' => $request->toArray()['X_PERSONAL_TOKEN']['id'],
            ];
            $this->adminLoggingService->CreateAdminLog($inputs);
        }
    }
}
