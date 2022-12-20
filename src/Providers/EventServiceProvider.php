<?php

namespace Volistx\FrameworkKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Volistx\FrameworkKernel\Events\AdminRequestCompleted;
use Volistx\FrameworkKernel\Events\SubscriptionCancelled;
use Volistx\FrameworkKernel\Events\SubscriptionExpired;
use Volistx\FrameworkKernel\Events\UserRequestCompleted;
use Volistx\FrameworkKernel\Listeners\AdminRequestCompletedListener;
use Volistx\FrameworkKernel\Listeners\SubscriptionCancelledListener;
use Volistx\FrameworkKernel\Listeners\SubscriptionExpiredListener;
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
        SubscriptionCancelled::class => [
            SubscriptionCancelledListener::class,
        ],
        SubscriptionExpired::class => [
            SubscriptionExpiredListener::class,
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
                $events->listen($event, $listener);
            }
        }
    }
}
