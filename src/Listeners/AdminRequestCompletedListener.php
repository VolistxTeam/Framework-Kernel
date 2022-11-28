<?php

namespace App\Listeners;

namespace Volistx\FrameworkKernel\Listeners;

use Volistx\FrameworkKernel\Events\AdminRequestCompleted;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class AdminRequestCompletedListener
{
    private IAdminLoggingService $adminLoggingService;

    public function __construct(IAdminLoggingService $adminLoggingService)
    {
        $this->adminLoggingService = $adminLoggingService;
    }

    public function handle(AdminRequestCompleted $event)
    {
        $this->adminLoggingService->CreateAdminLog($event->inputs);
    }
}
