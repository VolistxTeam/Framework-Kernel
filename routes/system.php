<?php

/** @var Router $router */

/*
Please DO NOT touch any routes here!!
*/

use Laravel\Lumen\Routing\Router;

Router::group(['prefix' => 'sys-bin'], function () {
    Router::get('/ping', function () {
        return response('Hi!');
    });

    Router::group(['prefix' => 'admin', 'middleware' => 'auth.admin'], function () {
        Router::group(['prefix' => 'subscriptions'], function () {
            Router::group(['middleware' => ['filter.json']], function () {
                Router::post('/', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@CreateSubscription');
                Router::patch('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@UpdateSubscription');
                Router::post('/{subscription_id}/cancel', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@CancelSubscription');
                Router::post('/{subscription_id}/personal-tokens', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@CreatePersonalToken');
                Router::patch('/{subscription_id}/personal-tokens/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@UpdatePersonalToken');
            });

            Router::post('/{subscription_id}/uncancel', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@UncancelSubscription');

            Router::delete('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@DeleteSubscription');
            Router::get('/', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptions');
            Router::get('/{subscription_id}', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscription');
            Router::get('/{subscription_id}/logs', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptionLogs');
            Router::get('/{subscription_id}/usages', 'Volistx\FrameworkKernel\Http\Controllers\SubscriptionController@GetSubscriptionUsages');

            Router::delete('/{subscription_id}/personal-tokens/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@DeletePersonalToken');
            Router::patch('/{subscription_id}/personal-tokens/{token_id}/reset', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@ResetPersonalToken');
            Router::get('/{subscription_id}/personal-tokens/{token_id}', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@GetPersonalToken');
            Router::get('/{subscription_id}/personal-tokens', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@GetPersonalTokens');
            Router::post('/{subscription_id}/personal-tokens/sync', 'Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController@Sync');
        });

        Router::group(['prefix' => 'plans'], function () {
            Router::group(['middleware' => ['filter.json']], function () {
                Router::post('/', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@CreatePlan');
                Router::patch('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@UpdatePlan');
            });
            Router::delete('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@DeletePlan');
            Router::get('/', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@GetPlans');
            Router::get('/{plan_id}', 'Volistx\FrameworkKernel\Http\Controllers\PlanController@GetPlan');
        });

        Router::group(['prefix' => 'logs'], function () {
            Router::get('/', 'Volistx\FrameworkKernel\Http\Controllers\AdminLogController@GetAdminLogs');
            Router::get('/{log_id}', 'Volistx\FrameworkKernel\Http\Controllers\AdminLogController@GetAdminLog');
        });

        Router::group(['prefix' => 'user-logs'], function () {
            Router::get('/', 'Volistx\FrameworkKernel\Http\Controllers\UserLogController@GetUserLogs');
            Router::get('/{log_id}', 'Volistx\FrameworkKernel\Http\Controllers\UserLogController@GetUserLog');
        });
    });
});
