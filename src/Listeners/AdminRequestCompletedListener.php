<?php

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\AdminRequestCompleted;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class AdminRequestCompletedListener
{
    private IAdminLoggingService $adminLoggingService;

    /**
     * AdminRequestCompletedListener constructor.
     *
     * @param IAdminLoggingService $adminLoggingService
     */
    public function __construct(IAdminLoggingService $adminLoggingService)
    {
        $this->adminLoggingService = $adminLoggingService;
    }

    /**
     * Handle the event.
     *
     * @param AdminRequestCompleted $event
     * @return void
     */
    public function handle(AdminRequestCompleted $event)
    {
        // Create log using the provided service
        $this->adminLoggingService->CreateAdminLog($event->inputs);
    }
}