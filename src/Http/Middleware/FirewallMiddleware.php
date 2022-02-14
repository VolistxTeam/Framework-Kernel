<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

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

        $ipSet = new IPSet(config('volistx.firewall.blacklist', []));

        if ($ipSet->match($clientIP)) {
            return response('', 403);
        }

        $response = $next($request);
        $response->header('X-Protected-By', 'WebShield/3.25d');

        return $response;
    }
}
