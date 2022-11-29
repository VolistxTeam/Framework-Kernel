<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Volistx\FrameworkKernel\Events\AdminRequestCompleted;
use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Facades\Subscriptions;

class RequestLoggingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (PersonalTokens::getToken() && PersonalTokens::getToken()->hidden === false) {
            if (PersonalTokens::getToken()->disable_logging === false) {
                $inputs = [
                    'url'             => $request->fullUrl(),
                    'method'          => $request->method(),
                    'ip'              => $request->ip(),
                    'user_agent'      => $request->userAgent() ?? null,
                    'subscription_id' => Subscriptions::getSubscription()?->id,
                ];
                Event::dispatch(new UserRequestCompleted($inputs));
            }
        } elseif (AccessTokens::getToken()) {
            $inputs = [
                'url'             => $request->fullUrl(),
                'method'          => $request->method(),
                'ip'              => $request->ip(),
                'user_agent'      => $request->userAgent() ?? null,
                'access_token_id' => AccessTokens::getToken()?->id,
            ];
            Event::dispatch(new AdminRequestCompleted($inputs));
        }
    }
}
