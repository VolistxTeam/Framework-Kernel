<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Repositories\Interfaces\IUserLogRepository;
use Volistx\FrameworkKernel\Repositories\LocalUserLogRepository;
use Volistx\FrameworkKernel\Repositories\RemoteUserLogRepository;

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
