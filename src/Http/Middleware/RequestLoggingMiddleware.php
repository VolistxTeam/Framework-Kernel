<?php

namespace VolistxTeam\VSkeletonKernel\Http\Middleware;

use Closure;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IAdminLogRepository;
use VolistxTeam\VSkeletonKernel\Repositories\UserLogRepository;
use function config;

class RequestLoggingMiddleware
{
    private IAdminLogRepository $adminLogRepository;
    private UserLogRepository $userLogRepository;

    public function __construct(IAdminLogRepository $adminLogRepository, UserLogRepository $userLogRepository)
    {
        $this->adminLogRepository = $adminLogRepository;
        $this->userLogRepository = $userLogRepository;
    }

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        if ($request->X_PERSONAL_TOKEN) {
            $inputs = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'subscription_id' => $request->X_PERSONAL_TOKEN->subscription()->first()->id
            ];
            $this->userLogRepository->Create($inputs);
        } else if ($request->X_ACCESS_TOKEN) {
            $inputs = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'access_token_id' => $request->X_ACCESS_TOKEN->id
            ];
            $this->adminLogRepository->Create($inputs);
        }
    }
}
