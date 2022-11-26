<?php

namespace Volistx\FrameworkKernel\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'Volistx\FrameworkKernel\Events\UserRequestCompleted' => [
            'Volistx\FrameworkKernel\Listeners\UserRequestCompletedListener'
        ],
        'Volistx\FrameworkKernel\Events\AdminRequestCompleted' => [
            'Volistx\FrameworkKernel\Listeners\AdminRequestCompletedListener'
        ],
    ];

    public function shouldDiscoverEvents()
    {
        return false;
    }
}
