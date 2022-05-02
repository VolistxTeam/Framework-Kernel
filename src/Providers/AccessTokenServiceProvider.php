<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Helpers\AccessTokensCenter;
use Volistx\FrameworkKernel\Helpers\PermissionsCenter;

class AccessTokenServiceProvider extends ServiceProvider
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
        $this->app->scoped('AccessTokens', function ($app) {
            return $app->make(AccessTokensCenter::class);
        });
    }
}
