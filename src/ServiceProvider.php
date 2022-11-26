<?php

namespace Volistx\FrameworkKernel;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleClearCacheCommand;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Laravel\Lumen\Routing\Router;
use Volistx\FrameworkKernel\Console\Commands\AccessKeyDeleteCommand;
use Volistx\FrameworkKernel\Console\Commands\AccessKeyGenerateCommand;
use Volistx\FrameworkKernel\Console\Commands\SubscriptionStatusCronCommand;
use Volistx\FrameworkKernel\Providers\AccessTokenServiceProvider;
use Volistx\FrameworkKernel\Providers\AdminLoggingServiceProvider;
use Volistx\FrameworkKernel\Providers\EventServiceProvider;
use Volistx\FrameworkKernel\Providers\GeoLocationServiceProvider;
use Volistx\FrameworkKernel\Providers\HMACServiceProvider;
use Volistx\FrameworkKernel\Providers\KeysServiceProvider;
use Volistx\FrameworkKernel\Providers\MessagesServiceProvider;
use Volistx\FrameworkKernel\Providers\PermissionsServiceProvider;
use Volistx\FrameworkKernel\Providers\PersonalTokenServiceProvider;
use Volistx\FrameworkKernel\Providers\PlansServiceProvider;
use Volistx\FrameworkKernel\Providers\SubscriptionServiceProvider;
use Volistx\FrameworkKernel\Providers\UserLoggingServiceProvider;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(Router $router, GateContract $gate): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/volistx.php', 'volistx');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register All Required Providers
        $serviceProvider = [
            AccessTokenServiceProvider::class,
            SubscriptionServiceProvider::class,
            AdminLoggingServiceProvider::class,
            GeoLocationServiceProvider::class,
            HMACServiceProvider::class,
            KeysServiceProvider::class,
            MessagesServiceProvider::class,
            PermissionsServiceProvider::class,
            PersonalTokenServiceProvider::class,
            PlansServiceProvider::class,
            UserLoggingServiceProvider::class,
            EventServiceProvider::class
        ];

        foreach ($serviceProvider as $provider) {
            $this->app->register($provider);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/system.php');

        $this->loadTranslationsFrom(__DIR__.'/../locales', 'volistx');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AccessKeyDeleteCommand::class,
                AccessKeyGenerateCommand::class,
                SubscriptionStatusCronCommand::class,
                ScheduleListCommand::class,
                ScheduleClearCacheCommand::class,
            ]);
        }

        // publish config and migration
        $this->publishes([
            __DIR__.'/../config/volistx.php'  => config_path('volistx.php'),
            __DIR__.'/../database/migrations' => database_path('migrations'),
            __DIR__.'/../locales'             => resource_path('lang/vendor/volistx'),
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('volistx-subscription:cron')->everyFiveMinutes();
        });
    }
}
