<?php

namespace VolistxTeam\VSkeletonKernel\Providers;

use Illuminate\Support\ServiceProvider;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IUserLogRepository;
use VolistxTeam\VSkeletonKernel\Repositories\LocalUserLogRepository;
use VolistxTeam\VSkeletonKernel\Repositories\RemoteUserLogRepository;

class UserLoggingRepositoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register()
    {
        if (config('volistx.logging.userLogMode') === 'local') {
            $this->app->bind(IUserLogRepository::class, LocalUserLogRepository::class);
        } else {
            $this->app->bind(IUserLogRepository::class, RemoteUserLogRepository::class);
        }
    }
}
