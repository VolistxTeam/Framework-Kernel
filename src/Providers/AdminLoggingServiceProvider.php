<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;
use Volistx\FrameworkKernel\Services\LocalAdminLoggingService;
use Volistx\FrameworkKernel\Services\RemoteAdminLoggingService;

class AdminLoggingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register()
    {
        if (config('volistx.logging.adminLogMode') === 'local') {
            $this->app->bind(IAdminLoggingService::class, LocalAdminLoggingService::class);
        } else {
            $this->app->bind(IAdminLoggingService::class, RemoteAdminLoggingService::class);
        }
    }
}
