<?php

namespace VolistxTeam\VSkeletonKernel\Http\Middleware;

use Closure;
use function config;
use Illuminate\Http\Request;
use function response;
use Wikimedia\IPSet;

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
