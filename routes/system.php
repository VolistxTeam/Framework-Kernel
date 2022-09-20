<?php

/** @var Router $router */

/*
Please DO NOT touch any routes here!!
*/

use Laravel\Lumen\Routing\Router;

$router->group(['prefix' => 'sys-bin'], function () use ($router) {
    $router->get('/ping', function () {
        return response('Hi!');
    });

    $router->group(['prefix' => 'admin', 'middleware' => 'auth.admin'], function () use ($router) {
        $router->group(['prefix' => 'subscriptions'], function () use ($router) {
            $router->group(['middleware' => ['filter.json']], function () use ($router) {
                $router->post('/', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@CreateSubscription');
                $router->patch('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@UpdateSubscription');

                $router->post('/{subscription_id}/personal-tokens', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@CreatePersonalToken');
                $router->patch('/{subscription_id}/personal-tokens/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@UpdatePersonalToken');
            });

            $router->delete('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@DeleteSubscription');
            $router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptions');
            $router->get('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscription');
            $router->get('/{subscription_id}/logs', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptionLogs');
            $router->get('/{subscription_id}/usages', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptionUsages');

            $router->delete('/{subscription_id}/personal-tokens/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@DeletePersonalToken');
            $router->patch('/{subscription_id}/personal-tokens/{token_id}/reset', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@ResetPersonalToken');
            $router->get('/{subscription_id}/personal-tokens/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@GetPersonalToken');
            $router->get('/{subscription_id}/personal-tokens', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@GetPersonalTokens');
            $router->post('/{subscription_id}/personal-tokens/sync', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@Sync');
        });

        $router->group(['prefix' => 'plans'], function () use ($router) {
            $router->group(['middleware' => ['filter.json']], function () use ($router) {
                $router->post('/', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@CreatePlan');
                $router->patch('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@UpdatePlan');
            });
            $router->delete('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@DeletePlan');
            $router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@GetPlans');
            $router->get('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@GetPlan');
        });

        $router->group(['prefix' => 'logs'], function () use ($router) {
            $router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\AdminLogController@GetAdminLogs');
            $router->get('/{log_id}', 'Volistx\FrameworkKernel\Http\Controllers\AdminLogController@GetAdminLog');
        });

        $router->group(['prefix' => 'user-logs'], function () use ($router) {
            $router->get('/', 'Volistx\FrameworkKernel\Http\Controllers\UserLogController@GetUserLogs');
            $router->get('/{log_id}', 'Volistx\FrameworkKernel\Http\Controllers\UserLogController@GetUserLog');
        });
    });
});
