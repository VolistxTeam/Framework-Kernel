<?php
namespace Volistx\FrameworkKernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Admins\IPValidationRule;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\AccessTokenRepository;
use function response;

class AdminAuthMiddleware
{
    private AccessTokenRepository $accessTokenRepository;

    /**
     * AdminAuthMiddleware constructor.
     *
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Authenticate the access token
        $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());
        if (!$token) {
            return response()->json(Messages::E401(), 401);
        }

        AccessTokens::setToken($token);

        // Add extra validators in the required order
        // To be refactored to detect all classes with a base of ValidationRuleBase
        // and create an instance of them passing parameters, and ordering them by id
        $validators = [
            new IPValidationRule($request),
        ];

        foreach ($validators as $validator) {
            $result = $validator->validate();
            if ($result !== true) {
                return response()->json($result['message'], $result['code']);
            }
        }

        AccessTokens::setToken($token);

        return $next($request);
    }
}