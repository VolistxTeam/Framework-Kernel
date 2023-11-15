<?php
namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\Messages;
use function response;

class JsonBodyValidationFilteringMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the request is in JSON format
        if (!$request->isJson()) {
            return response()->json(Messages::E400(), 400);
        }

        // Continue processing the request
        return $next($request);
    }
}