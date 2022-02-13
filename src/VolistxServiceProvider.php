<?php

namespace VolistxTeam\VSkeletonKernel;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Routing\Router;
use VolistxTeam\VSkeletonKernel\Console\Commands\DeleteCommand;
use VolistxTeam\VSkeletonKernel\Console\Commands\GenerateCommand;

class VolistxServiceProvider extends ServiceProvider
{
    public function boot(Router $router, GateContract $gate)
    {
        $this->publishes([
            __DIR__.'/../config/volistx.php' => config_path('volistx.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        require __DIR__.'/../routes/system.php';

        if ($this->app->runningInConsole()) {
            $this->commands([
                DeleteCommand::class,
                GenerateCommand::class,
            ]);
        }
    }
}
