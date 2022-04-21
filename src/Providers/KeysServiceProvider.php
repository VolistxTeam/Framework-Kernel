<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Helpers\KeysCenter;
use Volistx\FrameworkKernel\Helpers\MessagesCenter;

class KeysServiceProvider extends ServiceProvider
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
        $this->app->bind('keys', function () {
            return new KeysCenter();
        });
    }
}
