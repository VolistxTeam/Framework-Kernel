<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Repositories\UserLogRepository;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class UserRequestCompletedListener
{
    private IUserLoggingService $userLoggingService;

    public function __construct(IUserLoggingService $userLoggingService)
    {
        $this->userLoggingService = $userLoggingService;
    }

    public function handle(UserRequestCompleted $event)
    {
        $this->userLoggingService->CreateUserLog([
            'url' => $event->url,
            'method' => $event->method,
            'ip' => $event->ip,
            'user_agent' => $event->user_agent,
            'subscription_id' => $event->subscription_id
        ]);
    }
}
