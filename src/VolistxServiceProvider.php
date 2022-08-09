<?php

namespace Volistx\FrameworkKernel;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Routing\Router;
use Volistx\FrameworkKernel\Console\Commands\AccessKeyDeleteCommand;
use Volistx\FrameworkKernel\Console\Commands\AccessKeyGenerateCommand;
use Volistx\FrameworkKernel\Providers\AccessTokenServiceProvider;
use Volistx\FrameworkKernel\Providers\AdminLoggingServiceProvider;
use Volistx\FrameworkKernel\Providers\GeoLocationServiceProvider;
use Volistx\FrameworkKernel\Providers\HMACServiceProvider;
use Volistx\FrameworkKernel\Providers\KeysServiceProvider;
use Volistx\FrameworkKernel\Providers\MessagesServiceProvider;
use Volistx\FrameworkKernel\Providers\PermissionsServiceProvider;
use Volistx\FrameworkKernel\Providers\PersonalTokenServiceProvider;
use Volistx\FrameworkKernel\Providers\PlansServiceProvider;
use Volistx\FrameworkKernel\Providers\UserLoggingServiceProvider;

class VolistxServiceProvider extends ServiceProvider
{
    public function boot(Router $router, GateContract $gate): void
    {
        $this->publishes([
            __DIR__.'/../config/volistx.php' => config_path('volistx.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        // Register All Required Providers
        $this->app->register(AdminLoggingServiceProvider::class);
        $this->app->register(GeoLocationServiceProvider::class);
        $this->app->register(HMACServiceProvider::class);
        $this->app->register(KeysServiceProvider::class);
        $this->app->register(MessagesServiceProvider::class);
        $this->app->register(PermissionsServiceProvider::class);
        $this->app->register(UserLoggingServiceProvider::class);
        $this->app->register(AccessTokenServiceProvider::class);
        $this->app->register(PersonalTokenServiceProvider::class);
        $this->app->register(PlansServiceProvider::class);

        require __DIR__.'/../routes/system.php';

        if ($this->app->runningInConsole()) {
            $this->commands([
                AccessKeyDeleteCommand::class,
                AccessKeyGenerateCommand::class,
            ]);
        }
    }
}
