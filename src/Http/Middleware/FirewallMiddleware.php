<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use function config;
use Illuminate\Http\Request;
use function response;
use Volistx\FrameworkKernel\Facades\Messages;
use Wikimedia\IPSet;

class FirewallMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $clientIP = $request->getClientIp();

        $ipSet = new IPSet(config('volistx.firewall.blacklist', []));

        if ($ipSet->match($clientIP)) {
            return response()->json(Messages::E403(), 403);
        }

        if ($request->isMethod('OPTIONS')) {
            return response('', 200);
        }

        return $next($request)
            ->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE')
            ->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'))
            ->header('Access-Control-Allow-Origin', '*');
    }
}
