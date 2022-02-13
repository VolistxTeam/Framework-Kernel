<?php

namespace VolistxTeam\VSkeletonKernel\Providers;

use Illuminate\Support\ServiceProvider;
use VolistxTeam\VSkeletonKernel\Classes\PermissionsCenter;

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
