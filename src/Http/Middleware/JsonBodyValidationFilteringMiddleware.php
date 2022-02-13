<?php

namespace VolistxTeam\VSkeletonKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use function response;
use VolistxTeam\VSkeletonKernel\Facades\Messages;

class JsonBodyValidationFilteringMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->isJson()) {
            return response()->json(Messages::E400(), 400);
        }

        return $next($request);
    }
}
