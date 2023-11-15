<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class UserRequestCompletedListener
{
    private IUserLoggingService $userLoggingService;

    /**
     * UserRequestCompletedListener constructor.
     *
     * @param IUserLoggingService $userLoggingService The user logging service.
     */
    public function __construct(IUserLoggingService $userLoggingService)
    {
        $this->userLoggingService = $userLoggingService;
    }

    /**
     * Handle the UserRequestCompleted event.
     *
     * @param UserRequestCompleted $event The UserRequestCompleted event instance.
     *
     * @return void
     */
    public function handle(UserRequestCompleted $event)
    {
        // Create log using user service
        $this->userLoggingService->CreateUserLog($event->inputs);
    }
}
