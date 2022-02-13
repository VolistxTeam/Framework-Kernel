<?php

namespace VolistxTeam\VSkeletonKernel\Providers;

use Illuminate\Support\ServiceProvider;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IAdminLogRepository;
use VolistxTeam\VSkeletonKernel\Repositories\LocalAdminLogRepository;
use VolistxTeam\VSkeletonKernel\Repositories\RemoteAdminLogRepository;

class AdminLoggingRepositoryServiceProvider extends ServiceProvider
{
    public function boot()
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
