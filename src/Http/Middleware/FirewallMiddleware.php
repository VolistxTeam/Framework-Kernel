<?php

namespace VolistxTeam\VSkeletonKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wikimedia\IPSet;
use function config;
use function response;

class FirewallMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $clientIP = $request->getClientIp();

        $ipSet = new IPSet(config('volistx.firewall.ipBlacklist', []));

        if ($ipSet->match($clientIP)) {
            return response('', 403);
        }

        $response = $next($request);
        $response->header('X-Protected-By', 'WebShield/3.16');

        return $response;
    }
}
