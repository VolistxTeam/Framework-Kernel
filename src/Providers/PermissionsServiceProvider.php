<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Helpers\PermissionsCenter;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('permissions', function () {
            return new PermissionsCenter();
        });
    }
}
