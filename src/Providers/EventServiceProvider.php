<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Events\AdminRequestCompleted;
use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Listeners\AdminRequestCompletedListener;
use Volistx\FrameworkKernel\Listeners\UserRequestCompletedListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRequestCompleted::class => [
            UserRequestCompletedListener::class,
        ],
        AdminRequestCompleted::class => [
            AdminRequestCompletedListener::class,
        ],
    ];

    public function register()
    {
        $this->boot();
    }

    public function boot()
    {
        $events = app('events');
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $listenerExists = $events->hasListeners($event);
                if (!$listenerExists) {
                    $events->listen($event, $listener);
                }
            }
        }
    }
}
