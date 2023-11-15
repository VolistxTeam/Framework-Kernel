<?php
namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\Messages;
use Wikimedia\IPSet;
use function config;
use function response;

class FirewallMiddleware
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
        // Get the client IP address
        $clientIP = $request->getClientIp();

        // Create an IPSet with the configured blacklist
        $ipSet = new IPSet(config('volistx.firewall.blacklist', []));

        // Check if the client IP is in the blacklist
        if ($ipSet->match($clientIP)) {
            return response()->json(Messages::E403(), 403);
        }

        // Handle preflight OPTIONS request for CORS
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Origin', '*');
        }

        // Add CORS headers to the response
        return $next($request)
            ->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->header('Access-Control-Allow-Origin', '*');
    }
}