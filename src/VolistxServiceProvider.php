<?php

namespace VolistxTeam\VSkeletonKernel;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Laravel\Lumen\Routing\Router;
use Illuminate\Support\ServiceProvider;
use VolistxTeam\VSkeletonKernel\Console\Commands\DeleteCommand;
use VolistxTeam\VSkeletonKernel\Console\Commands\GenerateCommand;

class VolistxServiceProvider extends ServiceProvider
{
    public function boot(Router $router, GateContract $gate)
    {
        $this->publishes([
            __DIR__.'/../config/firewall.php' => config_path('firewall.php'),
            __DIR__.'/../config/geoip.php' => config_path('geoip.php'),
            __DIR__.'/../config/hashing.php' => config_path('hashing.php'),
            __DIR__.'/../config/laravelcloudflare.php' => config_path('laravelcloudflare.php'),
            __DIR__.'/../config/log.php' => config_path('log.php'),
            __DIR__.'/../config/responsecache.php' => config_path('responsecache.php'),
            __DIR__.'/../config/swoole_http.php' => config_path('swoole_http.php'),
            __DIR__.'/../config/swoole_websocket.php' => config_path('swoole_websocket.php'),
            __DIR__.'/../config/trustedproxy.php' => config_path('trustedproxy.php'),
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