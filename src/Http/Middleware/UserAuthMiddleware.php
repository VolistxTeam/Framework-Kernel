<?php

namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Repositories\PersonalTokenRepository;

class UserAuthMiddleware
{
    private PersonalTokenRepository $personalTokenRepository;

    /**
     * UserAuthMiddleware constructor.
     *
     * @param PersonalTokenRepository $personalTokenRepository
     */
    public function __construct(PersonalTokenRepository $personalTokenRepository)
    {
        $this->personalTokenRepository = $personalTokenRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Authenticate the personal token
        $token = $this->personalTokenRepository->AuthPersonalToken($request->bearerToken());
        if (!$token) {
            return response()->json(Messages::E401(), 401);
        }

        PersonalTokens::setToken($token);

        foreach (config('volistx.validators') as $validatorClass) {
            // Instantiate the validator class passing the request and process it
            $validator = new $validatorClass($request);
            $result = $validator->validate();
            if ($result !== true) {
                return response()->json($result['message'], $result['code']);
            }
        }

        return $next($request);
    }
}
