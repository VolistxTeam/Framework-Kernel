<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\UserRequestCompleted;
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
        $this->userLoggingService->CreateUserLog($event->inputs);
    }
}
