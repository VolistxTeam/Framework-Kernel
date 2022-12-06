<?php

/** @var Router $router */

/*
Please DO NOT touch any routes here!!
*/

use Laravel\Lumen\Routing\Router;

$this->app->router->group(['prefix' => 'sys-bin'], function () {
    $this->app->router->get('/ping', function () {
        return response('Hi!');
    });

    $this->app->router->get('/timestamp', function () {
        return response(time());
    });

    $this->app->router->group(['prefix' => 'admin', 'middleware' => 'auth.admin'], function () {
        // Users
        $this->app->router->group(['prefix' => 'users'], function () {
            $this->app->router->group(['middleware' => ['filter.json']], function () {
                $this->app->router->post('/', 'Volistx\FrameworkKernel\Http\Controllers\UserController@CreateUser');
                $this->app->router->patch('/{user_id}', 'Volistx\FrameworkKernel\Http\Controllers\UserController@UpdateUser');
            });
            $this->app->router->delete('/{user_id}', 'Volistx\FrameworkKernel\Http\Controllers\UserController@DeleteUser');
            $this->app->router->get('/{user_id}', 'Volistx\FrameworkKernel\Http\Controllers\UserController@GetUser');


            $this->app->router->group(['prefix' => '/{user_id}/'], function () {

                // Subscriptions
                $this->app->router->group(['prefix' => 'subscriptions'], function () {
                    $this->app->router->group(['middleware' => ['filter.json']], function () {
                        $this->app->router->post('/', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@CreateSubscription');
                        $this->app->router->patch('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@MutateSubscription');
                        $this->app->router->post('/{subscription_id}/cancel', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@CancelSubscription');
                    });

                    $this->app->router->post('/{subscription_id}/uncancel', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@UncancelSubscription');
                    $this->app->router->delete('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@DeleteSubscription');
                    $this->app->router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptions');
                    $this->app->router->get('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscription');
                    $this->app->router->get('/{subscription_id}/logs', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptionLogs');
                    $this->app->router->get('/{subscription_id}/usages', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptionUsages');
                });

                // Personal tokens
                $this->app->router->group(['prefix' => 'personal-tokens'], function () {
                    $this->app->router->group(['middleware' => ['filter.json']], function () {
                        $this->app->router->post('/', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@CreatePersonalToken');
                        $this->app->router->patch('/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@UpdatePersonalToken');
                    });
                    $this->app->router->delete('/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@DeletePersonalToken');
                    $this->app->router->patch('/{token_id}/reset', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@ResetPersonalToken');
                    $this->app->router->get('/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@GetPersonalToken');
                    $this->app->router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@GetPersonalTokens');
                    $this->app->router->post('/sync', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@Sync');
                });
            });
        });


        $this->app->router->group(['prefix' => 'plans'], function () {
            $this->app->router->group(['middleware' => ['filter.json']], function () {
                $this->app->router->post('/', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@CreatePlan');
                $this->app->router->patch('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@UpdatePlan');
            });
            $this->app->router->delete('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@DeletePlan');
            $this->app->router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@GetPlans');
            $this->app->router->get('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@GetPlan');
        });

        $this->app->router->group(['prefix' => 'logs'], function () {
            $this->app->router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\AdminLogController@GetAdminLogs');
            $this->app->router->get('/{log_id}', 'Volistx\FrameworkKernel\Http\Controllers\AdminLogController@GetAdminLog');
        });

        $this->app->router->group(['prefix' => 'user-logs'], function () {
            $this->app->router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\UserLogController@GetUserLogs');
            $this->app->router->get('/{log_id}', 'Volistx\FrameworkKernel\Http\Controllers\UserLogController@GetUserLog');
        });
    });
});
