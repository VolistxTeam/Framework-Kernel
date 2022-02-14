<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Repositories\Interfaces\IAdminLogRepository;
use Volistx\FrameworkKernel\Repositories\LocalAdminLogRepository;
use Volistx\FrameworkKernel\Repositories\RemoteAdminLogRepository;

class AdminLoggingRepositoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register()
    {
        if (config('volistx.logging.adminLogMode') === 'local') {
            $this->app->bind(IAdminLogRepository::class, LocalAdminLogRepository::class);
        } else {
            $this->app->bind(IAdminLogRepository::class, RemoteAdminLogRepository::class);
        }
    }
}
