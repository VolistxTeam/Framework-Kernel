<?php

/** @var Router $router */

/*
Please DO NOT touch any routes here!!
*/

use Laravel\Lumen\Routing\Router;

$router->group(['prefix' => 'sys-bin'], function () use ($router) {
    $router->get('/ping', function () {
        return response('pong');
    });

    $router->group(['prefix' => 'admin', 'middleware' => 'auth.admin'], function () use ($router) {
        $router->group(['prefix' => 'subscriptions'], function () use ($router) {
            $router->group(['middleware' => ['filter.json']], function () use ($router) {
                $router->post('/', 'VolistxTeam\VSkeletonKernel\Http\Controllers\SubscriptionController@CreateSubscription');
                $router->put('/{subscription_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\SubscriptionController@UpdateSubscription');


                $router->post('/{subscription_id}/personal-tokens', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PersonalTokenController@CreatePersonalToken');
                $router->put('/{subscription_id}/personal-tokens/{token_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PersonalTokenController@UpdatePersonalToken');

            });

            $router->delete('/{subscription_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\SubscriptionController@DeleteSubscription');
            $router->get('/', 'VolistxTeam\VSkeletonKernel\Http\Controllers\SubscriptionController@GetSubscriptions');
            $router->get('/{subscription_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\SubscriptionController@GetSubscription');
            $router->get('/{subscription_id}/logs', 'VolistxTeam\VSkeletonKernel\Http\Controllers\SubscriptionController@GetSubscriptionLogs');


            $router->delete('/{subscription_id}/personal-tokens/{token_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PersonalTokenController@DeletePersonalToken');
            $router->put('/{subscription_id}/personal-tokens/{token_id}/reset', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PersonalTokenController@ResetPersonalToken');
            $router->get('/{subscription_id}/personal-tokens/{token_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PersonalTokenController@GetPersonalToken');
            $router->get('/{subscription_id}/personal-tokens', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PersonalTokenController@GetPersonalTokens');
        });

        $router->group(['prefix' => 'plans'], function () use ($router) {
            $router->group(['middleware' => ['filter.json']], function () use ($router) {
                $router->post('/', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PlanController@CreatePlan');
                $router->put('/{plan_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PlanController@UpdatePlan');
            });
            $router->delete('/{plan_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PlanController@DeletePlan');
            $router->get('/', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PlanController@GetPlans');
            $router->get('/{plan_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\PlanController@GetPlan');
        });

        //TO DISCUSS THE LOGS HASSLE
        $router->group(['prefix' => 'logs'], function () use ($router) {
            $router->get('/', 'VolistxTeam\VSkeletonKernel\Http\Controllers\AdminLogController@GetAdminLogs');
            $router->get('/{log_id}', 'VolistxTeam\VSkeletonKernel\Http\Controllers\AdminLogController@GetAdminLog');
        });
    });
});