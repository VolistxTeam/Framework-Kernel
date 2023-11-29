<?php

namespace Volistx\FrameworkKernel\Tests;
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        // Code before application created.

        $this->afterApplicationCreated(function () {
            // Code after application created.
        });

        $this->beforeApplicationDestroyed(function () {
            // Code before application destroyed.
        });

        parent::setUp();

        $this->loadMigrationsFrom([
            '--path' => realpath(__DIR__ . '/../../database/migrations'),
            '--realpath' => true
        ]);

        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }

    protected function getEnvironmentSetUp($app)
    {
        # Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            'Volistx\FrameworkKernel\ServiceProvider',
        ];
    }

    protected function TestPermissions($token, string $key, string $method, string $route, array $permissions, $input = []): void
    {
        foreach ($permissions as $permissionName => $permissionResult) {
            $token->permissions = [$permissionName];
            $token->save();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $key,
                'Content-Type: application/json'
            ])->{$method}($route,$input);

            $response->assertStatus($permissionResult);
        }
    }
}