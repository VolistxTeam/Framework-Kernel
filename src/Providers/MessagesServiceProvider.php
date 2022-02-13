<?php

namespace VolistxTeam\VSkeletonKernel\Providers;

use Illuminate\Support\ServiceProvider;
use VolistxTeam\VSkeletonKernel\Classes\MessagesCenter;

class MessagesServiceProvider extends ServiceProvider
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
        $this->app->bind('messages', function () {
            return new MessagesCenter();
        });
    }
}
