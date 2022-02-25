<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;
use Volistx\FrameworkKernel\Services\LocalUserLoggingService;
use Volistx\FrameworkKernel\Services\RemoteUserLoggingService;

class UserLoggingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register()
    {
        if (config('volistx.logging.userLogMode') === 'local') {
            $this->app->bind(IUserLoggingService::class, LocalUserLoggingService::class);
        } else {
            $this->app->bind(IUserLoggingService::class, RemoteUserLoggingService::class);
        }
    }
}
