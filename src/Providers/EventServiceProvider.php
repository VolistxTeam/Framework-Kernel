<?php

namespace Volistx\FrameworkKernel\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Listeners\UserRequestCompletedListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRequestCompleted::class => [
            UserRequestCompletedListener::class
        ],
    ];

    public function shouldDiscoverEvents()
    {
        return false;
    }
}
